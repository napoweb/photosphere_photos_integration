<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Service;

use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/** Private app-specific panorama derivatives. Never expose appdata paths publicly. */
final class PanoramaCache {
    public const APP_ID = 'photosphere_photos_integration';
    private const CACHE_DIR = 'panorama_derivatives_v1';
    private const MAX_SOURCE_BYTES = 150000000;
    public const ENABLED_KEY = 'panorama_cache_enabled';
    public const WORKERS_KEY = 'panorama_cache_workers';
    public const MAX_WORKERS = 3;

    public function maxWorkers(): int {
        return max(1, min(self::MAX_WORKERS, (int)$this->config->getAppValue(self::APP_ID, self::WORKERS_KEY, '1')));
    }

    public function isEnabled(): bool {
        return $this->config->getAppValue(self::APP_ID, self::ENABLED_KEY, 'yes') === 'yes';
    }

    public function __construct(
        private IAppData $appData,
        private IRootFolder $rootFolder,
        private IConfig $config,
        private LoggerInterface $logger,
    ) {}

    private function folder(): ?\OCP\Files\SimpleFS\ISimpleFolder {
        try {
            return $this->appData->getFolder(self::CACHE_DIR);
        } catch (\OCP\Files\NotFoundException $e) {
            try { return $this->appData->newFolder(self::CACHE_DIR); }
            catch (\Throwable $e) {
                $this->logger->warning('Photo Sphere panorama cache folder creation failed: {message}', ['message' => $e->getMessage(), 'app' => self::APP_ID]);
                return null;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Photo Sphere panorama cache folder access failed: {message}', ['message' => $e->getMessage(), 'app' => self::APP_ID]);
            return null;
        }
    }

    /** Cache keys follow source etag and size, so edits cannot serve stale content. */
    private function name(File $file, string $profile): string {
        $key = implode('|', [$file->getStorage()->getId(), $file->getId(), $file->getEtag(), $file->getSize()]);
        return $file->getId() . '-' . hash('sha256', $key) . '-' . $profile . '.jpg';
    }

    public function cached(File $file, string $profile): ?\OCP\Files\SimpleFS\ISimpleFile {
        if (!in_array($profile, ['mobile', 'desktop'], true)) { return null; }
        try {
            $folder = $this->folder();
            if ($folder === null || !$folder->fileExists($this->name($file, $profile))) { return null; }
            $result = $folder->getFile($this->name($file, $profile));
            return $result->getSize() > 0 ? $result : null;
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Only images still belonging to the user's tour are processed. One worker
     * per server installation may decode large panoramas at a time. This
     * filesystem lock is process-safe on a single-host Nextcloud installation.
     * Jobs unable to obtain it are safely skipped and can be queued again on
     * a later authorised tour visit.
     */
    public function warm(int $fileId, string $uid): void {
        if (!$this->isEnabled() || $fileId <= 0 || $uid === '') { return; }
        $raw = json_decode($this->config->getUserValue($uid, self::APP_ID, 'tour_drafts', '[]'), true);
        if (!is_array($raw)) { return; }
        $inTour = false;
        foreach ($raw as $tour) {
            if (!is_array($tour)) { continue; }
            foreach ($tour['images'] ?? [] as $item) {
                if (is_array($item) && (int)($item['id'] ?? 0) === $fileId) { $inTour = true; break 2; }
            }
        }
        if (!$inTour) { return; }

        // Each worker gets one non-blocking file lock slot. Slot 0 always exists,
        // preserving existing single-worker installations; further slots only
        // become available when the admin increases the configured limit.
        // The lock files are shared by PHP CLI workers on this host only.
        $instance = (string)$this->config->getSystemValue('instanceid', '');
        $lockBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . 'ppi-pano-' . hash('sha256', self::APP_ID . '|' . $instance);
        $handle = false;
        $slot = null;
        for ($i = 0, $limit = $this->maxWorkers(); $i < $limit; $i++) {
            $candidate = @fopen($lockBase . ($i === 0 ? '' : '-' . $i) . '.lock', 'c');
            if ($candidate === false) { continue; }
            if (@flock($candidate, LOCK_EX | LOCK_NB)) {
                $handle = $candidate;
                $slot = $i;
                break;
            }
            fclose($candidate);
        }
        if ($handle === false) {
            $this->logger->info('Photo Sphere panorama preparation deferred: all worker slots busy (file {fileId})', ['fileId' => $fileId, 'app' => self::APP_ID]);
            return;
        }
        try {
            if (!$this->isEnabled()) { return; }
            $this->logger->debug('Photo Sphere panorama preparation claimed worker slot {slot}', ['slot' => $slot, 'app' => self::APP_ID]);
            try {
                $home = $this->rootFolder->getUserFolder($uid);
                foreach ($home->getById($fileId) as $file) {
                    if (!$file instanceof File || !$file->isReadable() || $file->getMimetype() !== 'image/jpeg'
                        || !is_string($home->getRelativePath($file->getPath()))) { continue; }
                    $this->generate($file);
                    return;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Photo Sphere panorama background preparation failed: {message}', ['message' => $e->getMessage(), 'app' => self::APP_ID]);
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** Generate both sizes from one source decode, respecting the host's ImageMagick limits. */
    private function generate(File $file): void {
        if (!extension_loaded('imagick') || !class_exists('\Imagick')) {
            $this->logger->info('Photo Sphere panorama generation unavailable: Imagick not installed', ['app' => self::APP_ID]);
            return;
        }
        if ($file->getSize() > self::MAX_SOURCE_BYTES) {
            $this->logger->info('Photo Sphere panorama generation skipped: source too large (file {fileId})', ['fileId' => $file->getId(), 'app' => self::APP_ID]);
            return;
        }
        $folder = $this->folder();
        if ($folder === null) { return; }
        if ($this->cached($file, 'desktop') !== null && $this->cached($file, 'mobile') !== null) {
            // Clean obsolete versions even when no new encoding is required.
            $this->pruneOld($folder, $file);
            return;
        }
        $stream = $file->fopen('r');
        if (!is_resource($stream)) { return; }
        $tmp = tempnam(sys_get_temp_dir(), 'ppi-pano-');
        if ($tmp === false) { fclose($stream); return; }
        $out = @fopen($tmp, 'wb');
        if (!is_resource($out)) { fclose($stream); @unlink($tmp); return; }
        try {
            $copied = stream_copy_to_stream($stream, $out, self::MAX_SOURCE_BYTES + 1);
            fclose($stream);
            fclose($out);
            if ($copied === false || $copied > self::MAX_SOURCE_BYTES) { return; }
            // ImageMagick may otherwise spawn many threads for a single large JPEG.
            // Limit this worker only; do not touch global Nextcloud settings.
            try { \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_THREAD, 1); }
            catch (\Throwable $e) { /* Older Imagick builds may not support this limit. */ }
            $img = new \Imagick();
            try {
                $img->readImage($tmp);
                $img->setIteratorIndex(0);
                $w = $img->getImageWidth(); $h = $img->getImageHeight();
                // Only 2:1 JPEG panoramas; never upscale.
                if ($w < 512 || $h < 256 || $w > 32000 || $h > 16000 || abs($w / $h - 2) > 0.03) { return; }
                $img->stripImage();
                $img->setImageFormat('jpeg');
                $img->setImageCompressionQuality(85);
                $this->writeSize($folder, $file, 'desktop', $img, 8192);
                $this->writeSize($folder, $file, 'mobile', $img, 4096);
                $this->pruneOld($folder, $file);
                if ($this->cached($file, 'desktop') !== null && $this->cached($file, 'mobile') !== null) {
                    $this->logger->info('Photo Sphere panorama derivatives ready (file {fileId})', ['fileId' => $file->getId(), 'app' => self::APP_ID]);
                }
            } finally { $img->clear(); $img->destroy(); }
        } catch (\Throwable $e) {
            $this->logger->warning('Photo Sphere derivative creation skipped: {message}', ['message' => $e->getMessage(), 'app' => self::APP_ID]);
        } finally { @unlink($tmp); }
    }

    /**
     * Low-cost, tour-scoped cleanup. This runs at most once per user per day
     * when an authorised tour is visited (never scans global Nextcloud previews).
     * Only 20 different image IDs and up to 40 old files are processed per job.
     * Other tours, deleted tours and orphaned files are deliberately NOT purged.
     */
    public function cleanupForUser(string $uid): void {
        if ($uid === '' || !$this->isEnabled()) { return; }
        $raw = json_decode($this->config->getUserValue($uid, self::APP_ID, 'tour_drafts', '[]'), true);
        if (!is_array($raw)) { return; }
        $ids = [];
        foreach ($raw as $tour) {
            if (!is_array($tour) || !is_array($tour['images'] ?? null)) { continue; }
            foreach ($tour['images'] as $item) {
                $id = is_array($item) ? (int)($item['id'] ?? 0) : 0;
                if ($id > 0) { $ids[$id] = $id; }
            }
        }
        $ids = array_values($ids);
        if ($ids === []) { return; }
        sort($ids, SORT_NUMERIC);

        $cursorKey = 'panorama_cleanup_cursor';
        $cursor = max(0, (int)$this->config->getUserValue($uid, self::APP_ID, $cursorKey, '0'));
        $start = $cursor % count($ids);
        $batch = [];
        $batchSize = min(20, count($ids));
        for ($i = 0; $i < $batchSize; $i++) { $batch[] = $ids[($start + $i) % count($ids)]; }

        try {
            // Reuse the same private app-specific directory. Never traverse /appdata
            // recursively, and never access or remove the global preview cache.
            $folder = $this->appData->getFolder(self::CACHE_DIR);
            $byId = [];
            foreach ($folder->getDirectoryListing() as $entry) {
                $name = $entry->getName();
                if (!preg_match('/^([1-9][0-9]*)-[a-f0-9]{64}-(desktop|mobile)\.jpg$/D', $name, $matches)) { continue; }
                $byId[(int)$matches[1]][$name] = $entry;
            }

            $home = $this->rootFolder->getUserFolder($uid);
            $deleted = 0;
            foreach ($batch as $id) {
                if (!isset($byId[$id])) { continue; }
                // Validate the live source and user membership before deleting.
                // A missing, inaccessible or removed image means NO deletion.
                foreach ($home->getById($id) as $file) {
                    if (!$file instanceof File || !$file->isReadable() || $file->getMimetype() !== 'image/jpeg'
                        || !is_string($home->getRelativePath($file->getPath()))) { continue; }
                    $desktop = $this->name($file, 'desktop');
                    $mobile = $this->name($file, 'mobile');
                    // Both currently valid profiles must exist and be nonempty.
                    if ($this->cached($file, 'desktop') === null || $this->cached($file, 'mobile') === null) { break; }
                    foreach ($byId[$id] as $filename => $entry) {
                        if ($filename === $desktop || $filename === $mobile) { continue; }
                        try {
                            $entry->delete();
                            $deleted++;
                        } catch (\Throwable $e) {
                            $this->logger->warning('Photo Sphere outdated cache entry could not be deleted: {message}',
                                ['message' => $e->getMessage(), 'app' => self::APP_ID]);
                        }
                        if ($deleted >= 40) { break 3; }
                    }
                    break;
                }
            }
            $this->config->setUserValue($uid, self::APP_ID, $cursorKey, (string)(($start + $batchSize) % count($ids)));
            $this->logger->info('Photo Sphere panorama cache cleanup checked {count} tour images, deleted {deleted} outdated derivatives',
                ['count' => count($batch), 'deleted' => $deleted, 'app' => self::APP_ID]);
        } catch (\OCP\Files\NotFoundException $e) {
            // No panorama cache yet: nothing to clean.
        } catch (\Throwable $e) {
            $this->logger->warning('Photo Sphere panorama cache cleanup failed: {message}',
                ['message' => $e->getMessage(), 'app' => self::APP_ID]);
        }
    }

    /** Delete only outdated derivatives owned by this app and source file. */
    private function pruneOld(\OCP\Files\SimpleFS\ISimpleFolder $folder, File $file): void {
        if ($this->cached($file, 'desktop') === null || $this->cached($file, 'mobile') === null) { return; }
        $keep = [$this->name($file, 'desktop'), $this->name($file, 'mobile')];
        try {
            foreach ($folder->getDirectoryListing() as $entry) {
                $name = $entry->getName();
                if (str_starts_with($name, $file->getId() . '-') && !in_array($name, $keep, true)
                    && preg_match('/^[0-9]+-[a-f0-9]{64}-(desktop|mobile)\.jpg$/D', $name)) {
                    try { $entry->delete(); } catch (\Throwable $e) { /* Best effort cleanup. */ }
                }
            }
        } catch (\Throwable $e) { /* Noncritical on external storage backends. */ }
    }

    private function writeSize(\OCP\Files\SimpleFS\ISimpleFolder $folder, File $file, string $profile, \Imagick $source, int $width): void {
        if ($this->cached($file, $profile) !== null) { return; }
        // Mutate the image in descending resolution order; avoids cloning a
        // decoded 120 MP panorama (which can exhaust small ARM servers).
        try {
            $target = min($width, $source->getImageWidth());
            $height = max(1, (int)round($target * $source->getImageHeight() / $source->getImageWidth()));
            if ($target < $source->getImageWidth()) { $source->resizeImage($target, $height, \Imagick::FILTER_LANCZOS, 1); }
            $source->setImageFormat('jpeg');
            $source->setImageCompressionQuality(85);
            $bytes = $source->getImageBlob();
            if ($bytes === '') { return; }
            $name = $this->name($file, $profile);
            if ($folder->fileExists($name)) { return; }
            try { $folder->newFile($name, $bytes); }
            catch (\Throwable $e) {
                // Another worker may have completed this derivative simultaneously.
                if (!$folder->fileExists($name)) { throw $e; }
            }
        } finally { /* The owning generate() method releases the image. */ }
    }
}
