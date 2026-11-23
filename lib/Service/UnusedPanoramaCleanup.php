<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Service;

use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Fail-closed, database-independent cleanup of orphaned app-owned derivatives.
 * Never touches source files or Nextcloud's global preview directories.
 */
final class UnusedPanoramaCleanup {
    private const CACHE_DIR = 'panorama_derivatives_v1';
    private const GRACE_SECONDS = 604800; // 7 full days after first confirmed absence
    private const MAX_CACHE_ENTRIES = 20000;
    private const MAX_TOUR_ROWS = 10000;
    private const BATCH_FILES = 40;
    private const CANDIDATE_PREFIX = 'orphan_candidate_';
    private const CURSOR_KEY = 'orphan_cleanup_cursor';

    public function __construct(
        private IAppData $appData,
        private IRootFolder $rootFolder,
        private IDBConnection $db,
        private IConfig $config,
        private LoggerInterface $logger,
    ) {}

    /**
     * Returns referenced file IDs for ALL users with persisted Photo Sphere tours.
     * Any unreadable/malformed record, incomplete scan or database failure aborts
     * cleanup. QueryBuilder works with PostgreSQL, MariaDB and MySQL.
     *
     * @return array<int, true>
     */
    private function readAllTourReferences(
        ?array &$names = null,
        ?string $viewerUid = null,
        ?array &$ownReferences = null,
        ?array &$tourNames = null,
    ): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('userid', 'configvalue')
            ->from('preferences')
            ->where($qb->expr()->eq('appid', $qb->createNamedParameter(PanoramaCache::APP_ID)))
            ->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('tour_drafts')));

        $result = $qb->executeQuery();
        $references = [];
        $rows = 0;
        try {
            while (($row = $result->fetchAssociative()) !== false) {
                if (++$rows > self::MAX_TOUR_ROWS) {
                    throw new \RuntimeException('Too many tour records for bounded safe cleanup');
                }
                $raw = $row['configvalue'] ?? null;
                $isOwn = $viewerUid !== null && isset($row['userid']) && (string)$row['userid'] === $viewerUid;
                if (!is_string($raw) || strlen($raw) > 2000000) {
                    throw new \RuntimeException('Tour record is missing or unusually large');
                }
                $tours = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
                if (!is_array($tours) || !array_is_list($tours)) {
                    throw new \RuntimeException('Unexpected tour data structure');
                }
                foreach ($tours as $tour) {
                    if (!is_array($tour)) {
                        throw new \RuntimeException('Invalid tour structure');
                    }
                    $images = $tour['images'] ?? [];
                    if (!is_array($images)) {
                        throw new \RuntimeException('Invalid tour images');
                    }
                    foreach ($images as $image) {
                        if (!is_array($image) || !array_key_exists('id', $image)) {
                            throw new \RuntimeException('Invalid tour image reference');
                        }
                        if (!is_int($image['id']) && !is_string($image['id'])) {
                            throw new \RuntimeException('Invalid tour image ID type');
                        }
                        $rawId = (string)$image['id'];
                        if (!preg_match('/^[1-9][0-9]*$/D', $rawId)) {
                            throw new \RuntimeException('Unexpected tour image ID');
                        }
                        $id = (int)$rawId;
                        $references[$id] = true;
                        // Statistics may disclose names only from tours owned by the
                        // authenticated administrator, never from another user.
                        if ($isOwn && $ownReferences !== null) {
                            $ownReferences[$id] = true;
                        }
                        if ($isOwn && $tourNames !== null && is_string($tour['name'] ?? null)) {
                            $tourName = trim($tour['name']);
                            if ($tourName !== '') {
                                $tourNames[$id] ??= [];
                                $tourName = mb_substr($tourName, 0, 150);
                                if (!in_array($tourName, $tourNames[$id], true)) {
                                    $tourNames[$id][] = $tourName;
                                }
                            }
                        }
                        if ($isOwn && $names !== null && !isset($names[$id]) && is_string($image['name'] ?? null)) {
                            $label = trim($image['name']);
                            if ($label !== '') {
                                $names[$id] = mb_substr($label, 0, 180);
                            }
                        }
                    }
                }
            }
        } finally {
            $result->closeCursor();
        }
        return $references;
    }

    /**
     * Resolve readable original source filenames only in the requesting
     * administrator's current Nextcloud mount tree. No global owner lookup.
     * Statistics-only; fails closed for inaccessible or removed originals.
     * @param int[] $ids
     * @return array<int,string>
     */
    private function readableSourceNames(string $viewerUid, array $ids): array {
        $names = [];
        try {
            $home = $this->rootFolder->getUserFolder($viewerUid);
        } catch (\Throwable $e) {
            return $names;
        }
        foreach ($ids as $id) {
            try {
                foreach ($home->getById($id) as $node) {
                    if (!$node instanceof File || !$node->isReadable()) {
                        continue;
                    }
                    if (!is_string($home->getRelativePath($node->getPath()))) {
                        continue;
                    }
                    $name = trim($node->getName());
                    if ($name !== '') {
                        $names[$id] = mb_substr($name, 0, 180);
                        break;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return $names;
    }

    /**
     * Admin-only, read-only cache statistics. A complete cross-user tour scan
     * is mandatory before distinguishing used from unreferenced files.
     * The list is capped for admin page performance; totals include all entries.
     * No image decoding, cache creation, marker changes or deletion.
     *
     * @return array<string, mixed>
     */
    public function statistics(string $viewerUid): array {
        if ($viewerUid === '') {
            throw new \RuntimeException('Missing authenticated administrator');
        }
        // Fail closed: never label something "unused" if tour data cannot
        // be read completely. The controller reports a safe error instead.
        $names = [];
        $ownReferences = [];
        $tourNames = [];
        $references = $this->readAllTourReferences($names, $viewerUid, $ownReferences, $tourNames);
        $summary = [
            'tourReferences' => count($references),
            'cachedPanoramas' => 0,
            'cachedFiles' => 0,
            'totalBytes' => 0,
            'usedPanoramas' => 0,
            'usedBytes' => 0,
            'unusedPanoramas' => 0,
            'unusedBytes' => 0,
            'markedPanoramas' => 0,
            'markedBytes' => 0,
            'earliestExpiry' => null,
            'entries' => [],
            'shownLimit' => 150,
        ];
        try {
            $folder = $this->appData->getFolder(self::CACHE_DIR);
        } catch (\OCP\Files\NotFoundException $e) {
            return $summary;
        }
        $entriesById = [];
        $count = 0;
        foreach ($folder->getDirectoryListing() as $entry) {
            if (++$count > self::MAX_CACHE_ENTRIES) {
                throw new \RuntimeException('App cache exceeds the bounded statistics limit');
            }
            $filename = $entry->getName();
            if (!preg_match('/^([1-9][0-9]*)-[a-f0-9]{64}-(desktop|mobile)\.jpg$/D', $filename, $m)) {
                continue;
            }
            $id = (int)$m[1];
            $size = max(0, $entry->getSize());
            if (!isset($entriesById[$id])) {
                $entriesById[$id] = [
                    'id' => $id, 'bytes' => 0, 'files' => 0,
                    'desktop' => false, 'mobile' => false,
                    'filenames' => [],
                ];
            }
            $entriesById[$id]['bytes'] += $size;
            $entriesById[$id]['files']++;
            $entriesById[$id][$m[2]] = true;
            $entriesById[$id]['filenames'][] = $filename;
            $summary['cachedFiles']++;
            $summary['totalBytes'] += $size;
        }
        ksort($entriesById, SORT_NUMERIC);
        $summary['cachedPanoramas'] = count($entriesById);
        // Resolve only the bounded rows that may be displayed to this admin.
        $readableNames = $this->readableSourceNames(
            $viewerUid,
            array_slice(array_keys($entriesById), 0, $summary['shownLimit'])
        );
        foreach ($entriesById as $id => $row) {
            $used = isset($references[$id]);
            $summary[$used ? 'usedPanoramas' : 'unusedPanoramas']++;
            $summary[$used ? 'usedBytes' : 'unusedBytes'] += $row['bytes'];
            $since = null;
            $expires = null;
            if (!$used) {
                sort($row['filenames'], SORT_STRING);
                $fingerprint = hash('sha256', implode('|', $row['filenames']));
                $saved = json_decode($this->config->getAppValue(
                    PanoramaCache::APP_ID, self::CANDIDATE_PREFIX . $id, ''
                ), true);
                if (is_array($saved) && ($saved['fingerprint'] ?? null) === $fingerprint
                    && is_int($saved['since'] ?? null) && $saved['since'] > 0) {
                    $since = $saved['since'];
                    $expires = $since + self::GRACE_SECONDS;
                    $summary['markedPanoramas']++;
                    $summary['markedBytes'] += $row['bytes'];
                    if ($summary['earliestExpiry'] === null || $expires < $summary['earliestExpiry']) {
                        $summary['earliestExpiry'] = $expires;
                    }
                }
            }
            if (count($summary['entries']) < $summary['shownLimit']) {
                // Unknown ownership stays anonymous. Referenced exclusively by
                // someone else's tour also stays anonymous (including file ID).
                $hasOwnTour = isset($ownReferences[$id]);
                $sourceAvailable = isset($readableNames[$id]);
                $visibility = $hasOwnTour ? 'own' : ($sourceAvailable ? 'accessible' : ($used ? 'other' : 'unknown'));
                $summary['entries'][] = [
                    'id' => ($hasOwnTour || $sourceAvailable) ? $id : null,
                    'name' => $sourceAvailable ? $readableNames[$id] : ($hasOwnTour ? ($names[$id] ?? null) : null),
                    'sourceAvailable' => $sourceAvailable,
                    'tourNames' => $hasOwnTour ? ($tourNames[$id] ?? []) : [],
                    'visibility' => $visibility,
                    'used' => $used,
                    'markedSince' => $since,
                    'eligibleAfter' => $expires,
                    'bytes' => $row['bytes'],
                    'files' => $row['files'],
                    'desktop' => $row['desktop'],
                    'mobile' => $row['mobile'],
                ];
            }
        }
        return $summary;
    }

    /**
     * Does one bounded daily batch. A cache ID must be unreferenced for at least
     * seven days, with the same set of filenames, before its derivatives qualify.
     * Never delete when a full cross-user reference scan cannot be completed.
     */
    public function run(): void {
        if ($this->config->getAppValue(PanoramaCache::APP_ID, PanoramaCache::ENABLED_KEY, 'yes') !== 'yes') {
            return;
        }
        try {
            $folder = $this->appData->getFolder(self::CACHE_DIR);
        } catch (\OCP\Files\NotFoundException $e) {
            return;
        } catch (\Throwable $e) {
            $this->logger->warning('Photo Sphere orphan cleanup cannot read app cache: {error}', ['error' => $e->getMessage(), 'app' => PanoramaCache::APP_ID]);
            return;
        }

        try {
            // Enumerate only this application's own, flat folder. Other appdata
            // and global previews are never traversed.
            $byId = [];
            $entries = 0;
            foreach ($folder->getDirectoryListing() as $entry) {
                if (++$entries > self::MAX_CACHE_ENTRIES) {
                    throw new \RuntimeException('Cache too large for bounded cleanup; nothing deleted');
                }
                $filename = $entry->getName();
                if (!preg_match('/^([1-9][0-9]*)-[a-f0-9]{64}-(desktop|mobile)\.jpg$/D', $filename, $m)) {
                    continue;
                }
                $byId[(int)$m[1]][$filename] = $entry;
            }
            if ($byId === []) { return; }

            $references = $this->readAllTourReferences();
            $ids = array_keys($byId);
            sort($ids, SORT_NUMERIC);
            $cursor = max(0, (int)$this->config->getAppValue(PanoramaCache::APP_ID, self::CURSOR_KEY, '0'));
            $start = $cursor % count($ids);
            $checked = 0;
            $marked = 0;
            $deleted = 0;
            $now = time();
            $batch = min(count($ids), self::BATCH_FILES);

            for ($i = 0; $i < $batch; $i++) {
                $id = $ids[($start + $i) % count($ids)];
                $checked++;
                $key = self::CANDIDATE_PREFIX . $id;
                if (isset($references[$id])) {
                    // The panorama is still used by a user: clear any previous
                    // candidacy, even after previously being marked orphaned.
                    $this->config->deleteAppValue(PanoramaCache::APP_ID, $key);
                    continue;
                }

                $names = array_keys($byId[$id]);
                sort($names, SORT_STRING);
                $fingerprint = hash('sha256', implode('|', $names));
                $saved = json_decode($this->config->getAppValue(PanoramaCache::APP_ID, $key, ''), true);
                if (!is_array($saved) || ($saved['fingerprint'] ?? '') !== $fingerprint
                    || !is_int($saved['since'] ?? null) || $saved['since'] > $now) {
                    $this->config->setAppValue(PanoramaCache::APP_ID, $key,
                        json_encode(['since' => $now, 'fingerprint' => $fingerprint], JSON_THROW_ON_ERROR));
                    $marked++;
                    continue;
                }
                if ($now - $saved['since'] < self::GRACE_SECONDS) { continue; }

                // Re-read ALL tour references immediately before deletion.
                // This protects against tour changes during the earlier scan.
                $latestReferences = $this->readAllTourReferences();
                if (isset($latestReferences[$id])) {
                    $this->config->deleteAppValue(PanoramaCache::APP_ID, $key);
                    continue;
                }
                // If any derivative has changed in the meantime, restart grace.
                $currentNames = [];
                foreach ($folder->getDirectoryListing() as $entry) {
                    $name = $entry->getName();
                    if (preg_match('/^' . preg_quote((string)$id, '/') . '-[a-f0-9]{64}-(desktop|mobile)\.jpg$/D', $name)) {
                        $currentNames[] = $name;
                    }
                }
                sort($currentNames, SORT_STRING);
                if (hash('sha256', implode('|', $currentNames)) !== $fingerprint) {
                    $this->config->setAppValue(PanoramaCache::APP_ID, $key,
                        json_encode(['since' => $now, 'fingerprint' => hash('sha256', implode('|', $currentNames))], JSON_THROW_ON_ERROR));
                    continue;
                }

                foreach ($byId[$id] as $entry) {
                    try {
                        $entry->delete();
                        $deleted++;
                    } catch (\Throwable $e) {
                        $this->logger->warning('Photo Sphere orphan derivative deletion failed: {error}', ['error' => $e->getMessage(), 'app' => PanoramaCache::APP_ID]);
                    }
                }
                // Keep the marker if any file could not be deleted; future
                // batches may safely retry after cross-user revalidation.
                $stillExists = false;
                foreach ($names as $name) {
                    if ($folder->fileExists($name)) { $stillExists = true; break; }
                }
                if (!$stillExists) {
                    $this->config->deleteAppValue(PanoramaCache::APP_ID, $key);
                }
            }

            $this->config->setAppValue(PanoramaCache::APP_ID, self::CURSOR_KEY,
                (string)(($start + $batch) % count($ids)));
            $this->logger->info('Photo Sphere orphan cleanup: checked {checked}, newly marked {marked}, deleted {deleted} derivatives',
                ['checked' => $checked, 'marked' => $marked, 'deleted' => $deleted, 'app' => PanoramaCache::APP_ID]);
        } catch (\Throwable $e) {
            $this->logger->warning('Photo Sphere orphan cleanup skipped safely: {error}', ['error' => $e->getMessage(), 'app' => PanoramaCache::APP_ID]);
        }
    }
}
