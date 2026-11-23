<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\Files\IAppData;
use OCP\BackgroundJob\IJobList;
use OCA\PhotospherePhotosIntegration\BackgroundJob\PreparePanoramaJob;
use OCA\PhotospherePhotosIntegration\BackgroundJob\CleanupPanoramaCacheJob;
use OCA\PhotospherePhotosIntegration\Service\PanoramaCache;
use OCP\IPreview;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\ISession;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\Security\PasswordContext;
use OCP\HintException;
use OCP\Security\Bruteforce\IThrottler;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OCP\Files\File;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\ISystemTagManager;
use OCP\Util;

/** User-owned tour drafts with isolated, revocable token access for shared tours. */
final class TourController extends Controller {
    private const APP_ID = 'photosphere_photos_integration';
    private const MAX_TOURS = 30;
    private const MAX_IMAGES = 50;
    private const MAX_EXTRA_SHARES = 12;
    private const SHARE_INDEX_PREFIX = 'tourpub_';
    private const PICKER_PANORAMA_ONLY = 'picker_panorama_only';

    public function __construct(
        IRequest $request,
        private IConfig $config,
        private ICacheFactory $cacheFactory,
        private ISystemTagObjectMapper $tagMapper,
        private ISystemTagManager $tagManager,
        private IAppData $appData,
        private IUserSession $userSession,
        private IRootFolder $rootFolder,
        private IURLGenerator $urlGenerator,
        private IPreview $previewManager,
        private ISession $session,
        private IThrottler $throttler,
        private IEventDispatcher $eventDispatcher,
        private PanoramaCache $panoramaCache,
        private IJobList $jobList,
    ) {
        parent::__construct(self::APP_ID, $request);
    }

    /** Queue new and existing tour images without blocking the HTTP request. */
    private function queuePanoramas(string $uid, array $images): void {
        if (!$this->panoramaCache->isEnabled()) { return; }
        // At most one lightweight cleanup job per owner and 24h. It checks
        // only the owner's saved tour images and never scans global previews.
        try {
            $lastCleanup = (int)$this->config->getUserValue($uid, self::APP_ID, 'panorama_cleanup_queued_at', '0');
            if ($lastCleanup <= time() - 86400) {
                $this->jobList->add(CleanupPanoramaCacheJob::class, ['uid' => $uid]);
                $this->config->setUserValue($uid, self::APP_ID, 'panorama_cleanup_queued_at', (string)time());
            }
        } catch (\Throwable $e) { /* Cleanup must never interrupt tours. */ }
        foreach ($images as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id <= 0) { continue; }
            try {
                // Throttle recurring public visits; background jobs are deduplicated
                // by Nextcloud as well, but avoid spamming the queue on hot links.
                $key = 'pano_queue_' . $id;
                $last = (int)$this->config->getAppValue(self::APP_ID, $key, '0');
                if ($last > time() - 3600) { continue; }
                $this->jobList->add(PreparePanoramaJob::class, ['uid' => $uid, 'fileId' => $id]);
                $this->config->setAppValue(self::APP_ID, $key, (string)time());
            } catch (\Throwable $e) { /* Preview caching must never prevent normal tours. */ }
        }
    }

    private function tourWatermarkAllowed(string $id): bool {
        if ($id === '' || $id === 'none') { return false; }
        $raw = json_decode($this->config->getAppValue(self::APP_ID, 'watermark_tour_allowed', '[]'), true);
        if (!is_array($raw) || !in_array($id, $raw, true)) { return false; }
        if ($id === 'default') {
            return $this->config->getAppValue(self::APP_ID, 'watermark_hide_default', 'no') !== 'yes';
        }
        if ($id !== 'legacy' && !preg_match('/^wm-[a-f0-9]{16}$/D', $id)) { return false; }
        if ($id !== 'legacy') {
            $library = json_decode($this->config->getAppValue(self::APP_ID, 'watermark_library', '{}'), true);
            if (!is_array($library) || !array_key_exists($id, $library)) { return false; }
        }
        try {
            $filename = $id === 'legacy' ? 'watermark.png' : $id . '.png';
            return $this->appData->getFolder('watermark')->fileExists($filename);
        } catch (\Throwable $e) { return false; }
    }

    /** Token-scoped route; no admin preview URL or private app-data path is disclosed. */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicWatermark(string $token): DataDisplayResponse {
        $found = $this->authorizedTour($token);
        if ($found === null) { return new DataDisplayResponse('', 404); }
        $display = $found['tour']['display'] ?? [];
        $id = is_array($display) ? (string)($display['watermarkId'] ?? 'none') : 'none';
        if (empty($display['watermarkEnabled']) || !$this->tourWatermarkAllowed($id)) {
            return new DataDisplayResponse('', 404);
        }
        try {
            $image = $id === 'default'
                ? file_get_contents(__DIR__ . '/../../img/watermark-napohd.png')
                : $this->appData->getFolder('watermark')->getFile($id === 'legacy' ? 'watermark.png' : $id . '.png')->getContent();
            if (!is_string($image) || $image === '') { return new DataDisplayResponse('', 404); }
            return new DataDisplayResponse($image, 200, [
                'Content-Type' => 'image/png', 'Cache-Control' => 'no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $e) { return new DataDisplayResponse('', 404); }
    }

    private function userId(): ?string {
        return $this->userSession->getUser()?->getUID();
    }

    private function records(string $uid): array {
        $raw = json_decode($this->config->getUserValue($uid, self::APP_ID, 'tour_drafts', '[]'), true);
        if (!is_array($raw)) { return []; }
        return array_values(array_filter($raw, fn($t) => is_array($t) && isset($t['id']) && is_string($t['id'])));
    }

    private function persist(string $uid, array $tours): void {
        $this->config->setUserValue($uid, self::APP_ID, 'tour_drafts', json_encode(array_values($tours), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** Never include a password hash in a response, even for the tour owner. */
    private function publicRecord(array $tour): array {
        $tour['sharePasswordProtected'] = !empty($tour['sharePasswordHash']);
        unset($tour['sharePasswordHash']);
        if (isset($tour['shareLinks']) && is_array($tour['shareLinks'])) {
            $tour['shareLinks'] = array_map(static function (array $link): array {
                $link['passwordProtected'] = !empty($link['passwordHash']);
                unset($link['passwordHash']);
                return $link;
            }, array_values(array_filter($tour['shareLinks'], 'is_array')));
        }
        return $tour;
    }

    private function unlocked(string $token, array $tour): bool {
        if (empty($tour['sharePasswordHash'])) { return true; }
        $key = 'ppi_tour_unlock_' . hash('sha256', $token);
        $expected = hash('sha256', $token . '|' . $tour['sharePasswordHash']);
        $actual = $this->session->get($key);
        return is_string($actual) && hash_equals($expected, $actual);
    }

    private function authorizedTour(string $token): ?array {
        $found = $this->publicTour($token);
        if ($found === null || !$this->unlocked($token, $found['tour'])) { return null; }
        return $found;
    }

    private function error(string $message, int $status = 400): JSONResponse {
        return new JSONResponse(['success' => false, 'message' => $message], $status);
    }


    /** Private per-user start pages. Public endpoints and access grants are deliberately absent in phase 1. */
    private function startPageRecords(string $uid): array {
        $raw = json_decode($this->config->getUserValue($uid, self::APP_ID, 'tour_start_pages', '[]'), true);
        if (!is_array($raw)) { return []; }
        return array_values(array_filter($raw, static fn($page) => is_array($page) && isset($page['id']) && is_string($page['id'])));
    }

    private function persistStartPages(string $uid, array $pages): void {
        $this->config->setUserValue($uid, self::APP_ID, 'tour_start_pages',
            json_encode(array_values($pages), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    #[NoAdminRequired]
    public function listStartPages(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        return new JSONResponse(['success' => true, 'pages' => $this->startPageRecords($uid)]);
    }

    #[NoAdminRequired]
    public function createStartPage(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $name = trim((string)$this->request->getParam('name', ''));
        if ($name === '' || mb_strlen($name) > 100) { return $this->error('Name: 1 bis 100 Zeichen'); }
        $pages = $this->startPageRecords($uid);
        if (count($pages) >= 30) { return $this->error('Maximal 30 Startseiten pro Benutzer'); }
        $page = ['id' => bin2hex(random_bytes(12)), 'name' => $name,
            'headline' => $name, 'description' => '', 'layout' => 'hero',
            'headingColor' => '#ffffff', 'descriptionColor' => '#ffffff', 'backgroundColor' => '#000000',
            'designEffect' => 'subtle',
            'backgroundFileId' => 0, 'tileColumns' => 'auto',
            'tourIds' => [], 'created' => gmdate('c'), 'modified' => gmdate('c')];
        $pages[] = $page;
        $this->persistStartPages($uid, $pages);
        return new JSONResponse(['success' => true, 'page' => $page]);
    }

    #[NoAdminRequired]
    public function saveStartPage(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $name = trim((string)$this->request->getParam('name', ''));
        $headline = trim((string)$this->request->getParam('headline', ''));
        $description = trim((string)$this->request->getParam('description', ''));
        $layout = (string)$this->request->getParam('layout', 'hero');
        $headingColor = (string)$this->request->getParam('headingColor', '#ffffff');
        $descriptionColor = (string)$this->request->getParam('descriptionColor', '#ffffff');
        $designEffect = (string)$this->request->getParam('designEffect', 'subtle');
        $backgroundColor = (string)$this->request->getParam('backgroundColor', '#000000');
        $backgroundFileIdRaw = $this->request->getParam('backgroundFileId', '0');
        $backgroundFileId = filter_var($backgroundFileIdRaw, FILTER_VALIDATE_INT);
        $tileColumns = (string)$this->request->getParam('tileColumns', 'auto');
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($headline) > 120 ||
            mb_strlen($description) > 1000 || !in_array($layout, ['hero', 'split', 'gallery'], true) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/D', $headingColor) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/D', $descriptionColor) ||
            !in_array($designEffect, ['subtle', 'glass'], true) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/D', $backgroundColor) ||
            $backgroundFileId === false || $backgroundFileId < 0 ||
            !in_array($tileColumns, ['auto', '2', '3', '4', '5'], true)) {
            return $this->error('Ungültige Startseiten-Einstellungen');
        }
        $requested = json_decode((string)$this->request->getParam('tourIds', '[]'), true);
        if (!is_array($requested) || count($requested) > 30 || !array_is_list($requested)) {
            return $this->error('Ungültige Tour-Auswahl');
        }
        $ownedIds = array_column($this->records($uid), 'id');
        $tourIds = [];
        foreach ($requested as $tourId) {
            if (!is_string($tourId) || !in_array($tourId, $ownedIds, true)) {
                return $this->error('Mindestens eine Tour ist nicht verfügbar', 403);
            }
            if (!in_array($tourId, $tourIds, true)) { $tourIds[] = $tourId; }
        }
        if ($backgroundFileId > 0 && $this->image($uid, $backgroundFileId) === null) {
            return $this->error('Hintergrundbild nicht verfügbar oder keine Leseberechtigung', 403);
        }
        $pages = $this->startPageRecords($uid);
        foreach ($pages as &$page) {
            if ($page['id'] !== $id) { continue; }
            $page['name'] = $name;
            $page['headline'] = $headline;
            $page['description'] = $description;
            $page['layout'] = $layout;
            $page['headingColor'] = $headingColor;
            $page['descriptionColor'] = $descriptionColor;
            $page['designEffect'] = $designEffect;
            $page['backgroundColor'] = $backgroundColor;
            $page['backgroundFileId'] = $backgroundFileId;
            $page['tileColumns'] = $tileColumns;
            $page['tourIds'] = $tourIds;
            $page['modified'] = gmdate('c');
            $this->persistStartPages($uid, $pages);
            return new JSONResponse(['success' => true, 'page' => $page]);
        }
        unset($page);
        return $this->error('Startseite nicht gefunden', 404);
    }

    #[NoAdminRequired]
    public function duplicateStartPage(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $pages = $this->startPageRecords($uid);
        if (count($pages) >= 30) { return $this->error('Maximal 30 Startseiten pro Benutzer'); }
        foreach ($pages as $page) {
            if ($page['id'] !== $id) { continue; }
            $copy = $page;
            $copy['id'] = bin2hex(random_bytes(12));
            $copy['name'] = mb_substr($page['name'], 0, 92) . ' (Kopie)';
            $copy['created'] = gmdate('c');
            $copy['modified'] = gmdate('c');
            // Deliberately exclude any future access tokens, passwords and public state.
            unset($copy['publicToken'], $copy['sharePasswordHash'], $copy['shareLinks'], $copy['shareExpiresOn']);
            $pages[] = $copy;
            $this->persistStartPages($uid, $pages);
            return new JSONResponse(['success' => true, 'page' => $copy]);
        }
        return $this->error('Startseite nicht gefunden', 404);
    }

    #[NoAdminRequired]
    public function deleteStartPage(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $pages = $this->startPageRecords($uid);
        $remaining = array_values(array_filter($pages, static fn($page) => $page['id'] !== $id));
        if (count($remaining) === count($pages)) { return $this->error('Startseite nicht gefunden', 404); }
        $this->persistStartPages($uid, $remaining);
        return new JSONResponse(['success' => true]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        Util::addScript(self::APP_ID, 'tour-sphere');
        Util::addScript(self::APP_ID, 'tour-editor');
        return new TemplateResponse(self::APP_ID, 'tours', [
            'startPagesUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.listStartPages'),
            'apiUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.listTours'),
            'pickerPreferencesUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.savePickerPreferences'),
            'pickerPanoramaOnly' => ($this->userId() !== null && $this->config->getUserValue($this->userId(), self::APP_ID, self::PICKER_PANORAMA_ONLY, '0') === '1'),
            'filesUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.browse'),
            'imageUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.imageBytes'),
            'watermarkLibraryUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.watermark.tourLibrary'),
            'watermarkThumbnailUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.watermark.tourThumbnail'),
        ]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listTours(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $tours = $this->records($uid);
        foreach ($tours as $tour) { $this->queuePanoramas($uid, $tour['images'] ?? []); }
        return new JSONResponse(['success' => true, 'tours' => array_map(fn($tour) => $this->publicRecord($tour), $tours)]);
    }

    #[NoAdminRequired]
    public function create(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $name = trim((string)$this->request->getParam('name', ''));
        if ($name === '' || mb_strlen($name) > 100) { return $this->error('Tourname: 1 bis 100 Zeichen'); }
        $tours = $this->records($uid);
        if (count($tours) >= self::MAX_TOURS) { return $this->error('Maximal 30 Touren pro Benutzer'); }
        $tour = ['id' => bin2hex(random_bytes(12)), 'name' => $name, 'images' => [], 'links' => [],
            'created' => gmdate('c'), 'modified' => gmdate('c')];
        $tours[] = $tour;
        $this->persist($uid, $tours);
        return new JSONResponse(['success' => true, 'tour' => $this->publicRecord($tour)]);
    }

    /** Resolve a selected file inside the *current user's* accessible file tree. */
    private function image(string $uid, int $fileId): ?array {
        if ($fileId <= 0) { return null; }
        try {
            $home = $this->rootFolder->getUserFolder($uid);
            $matches = $home->getById($fileId);
            foreach ($matches as $node) {
                if (!$node instanceof File || $node->getMimetype() !== 'image/jpeg' || !$node->isReadable()) { continue; }
                $relative = $home->getRelativePath($node->getPath());
                if (!is_string($relative)) { continue; }
                return ['id' => $fileId, 'name' => $node->getName(), 'path' => $relative];
            }
        } catch (\Throwable $e) { /* Never leak filesystem paths or permission information. */ }
        return null;
    }

    /** The editor fetches JPEG bytes only for files currently readable by the signed-in user.
     * No public endpoint or filesystem path is exposed. */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function imageBytes(): Response {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $raw = $this->request->getParam('fileId', '');
        $id = filter_var($raw, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) { return $this->error('Ungültige Datei-ID'); }
        try {
            $home = $this->rootFolder->getUserFolder($uid);
            foreach ($home->getById($id) as $node) {
                if (!$node instanceof File || !$node->isReadable() || $node->getMimetype() !== 'image/jpeg') { continue; }
                if (!is_string($home->getRelativePath($node->getPath()))) { continue; }
                $stream = $node->fopen('r');
                if (!is_resource($stream)) { break; }
                $response = new StreamResponse($stream);
                $response->addHeader('Content-Type', 'image/jpeg');
                $response->addHeader('Cache-Control', 'private, no-store');
                $response->addHeader('X-Content-Type-Options', 'nosniff');
                return $response;
            }
        } catch (\Throwable $e) { /* Conceal internal paths and permissions. */ }
        return $this->error('Panorama nicht verfügbar oder keine Leseberechtigung', 404);
    }


    /** Persist the panorama-only checkbox for the signed-in user across devices. */
    #[NoAdminRequired]
    public function savePickerPreferences(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $raw = (string)$this->request->getParam('panoramaOnly', '');
        if ($raw !== '0' && $raw !== '1') { return $this->error('Ungültige Filtereinstellung'); }
        $this->config->setUserValue($uid, self::APP_ID, self::PICKER_PANORAMA_ONLY, $raw);
        return new JSONResponse(['success' => true, 'panoramaOnly' => $raw === '1']);
    }

    /** Read only the bounded beginning of a JPEG. Do not decode large panorama image data.
     * Strict GPano metadata detection: image ratio alone must never qualify. */
    private function hasPanoramaXmp(File $file): bool {
        $stream = null;
        try {
            $stream = $file->fopen('r');
            if (!is_resource($stream)) { return false; }
            // XMP normally lives in JPEG APP1 near the file header.
            $head = '';
            $remaining = 768 * 1024;
            while ($remaining > 0 && !feof($stream)) {
                $chunk = fread($stream, min(32768, $remaining));
                if ($chunk === false || $chunk === '') { break; }
                $head .= $chunk;
                $remaining -= strlen($chunk);
            }
            // Require the Adobe XMP header as well as a recognized GPano field.
            if (strpos($head, 'http://ns.adobe.com/xap/1.0/') === false) { return false; }
            return (bool)preg_match(
                '~(?:\bGPano:ProjectionType\s*=\s*["\x27]equirectangular["\x27]|<GPano:ProjectionType>\s*equirectangular\s*</GPano:ProjectionType>)~i',
                $head
            );
        } catch (\Throwable $e) {
            return false;
        } finally {
            if (is_resource($stream)) { fclose($stream); }
        }
    }

    private function isPanoramaFile(File $file): bool {
        try {
            $cache = $this->cacheFactory->createDistributed(self::APP_ID . '_pano_xmp');
            $key = hash('sha256', (string)$file->getId() . ':' . (string)$file->getMtime()
                . ':' . (string)$file->getSize() . ':' . (string)$file->getEtag());
            $cached = $cache->get($key);
            if ($cached === 'yes') { return true; }
            if ($cached === 'no') { return false; }
            $recognized = $this->hasPanoramaXmp($file);
            $cache->set($key, $recognized ? 'yes' : 'no', 30 * 86400);
            return $recognized;
        } catch (\Throwable $e) {
            // A cache failure must not expose ordinary JPEGs as verified panoramas.
            return $this->hasPanoramaXmp($file);
        }
    }


    /**
     * Match Nextcloud system tags for visible, readable JPEG files in this folder.
     * Batch file/tag relations; resolve each unique tag once with current-user
     * visibility enforcement. Only the matching file IDs leave this method.
     */
    private function matchingTaggedFileIds(array $listing, string $query, int $limit, bool $skipFilenameMatches): array {
        if ($query === '' || $limit <= 0) { return []; }
        $ids = [];
        foreach ($listing as $node) {
            if (count($ids) >= $limit) { break; }
            if (!$node instanceof File || !$node->isReadable()
                || $node->getMimetype() !== 'image/jpeg'
                || ($skipFilenameMatches && mb_stripos($node->getName(), $query) !== false)) { continue; }
            $ids[] = (string)$node->getId();
        }
        if (!$ids) { return []; }

        $mappings = [];
        try {
            foreach (array_chunk($ids, 200) as $chunk) {
                foreach ($this->tagMapper->getTagIdsForObjects($chunk, 'files') as $fileId => $tagIds) {
                    $mappings[(string)$fileId] = $tagIds;
                }
            }
        } catch (\Throwable $e) {
            // Tag subsystem problems must not break the original filename search.
            return [];
        }

        $user = $this->userSession->getUser();
        if ($user === null) { return []; }
        $tagsMatchingQuery = [];
        $matches = [];
        foreach ($mappings as $fileId => $tagIds) {
            foreach ($tagIds as $tagId) {
                $tagId = (string)$tagId;
                if (!array_key_exists($tagId, $tagsMatchingQuery)) {
                    $tagsMatchingQuery[$tagId] = false;
                    try {
                        $tags = $this->tagManager->getTagsByIds([$tagId], $user);
                        foreach ($tags as $tag) {
                            if (mb_stripos($tag->getName(), $query) !== false) {
                                $tagsMatchingQuery[$tagId] = true;
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                        // Missing or invisible tags are never returned as matches.
                    }
                }
                if ($tagsMatchingQuery[$tagId]) {
                    $matches[$fileId] = true;
                    break;
                }
            }
        }
        return $matches;
    }

    /** List a folder by Nextcloud file ID, using the current user's own mount tree. */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function browse(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $rawId = $this->request->getParam('folderId', '0');
        $folderId = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($folderId === false || $folderId < 0) { return $this->error('Ungültige Ordner-ID'); }
        try {
            $home = $this->rootFolder->getUserFolder($uid);
            $folder = $home;
            if ($folderId !== 0) {
                $folder = null;
                foreach ($home->getById($folderId) as $candidate) {
                    if ($candidate instanceof Folder && $candidate->isReadable()) {
                        $relative = $home->getRelativePath($candidate->getPath());
                        if (is_string($relative)) { $folder = $candidate; break; }
                    }
                }
                if ($folder === null) { return $this->error('Ordner nicht verfügbar oder nicht freigegeben', 404); }
            }

            $query = trim((string)$this->request->getParam('q', ''));
            $searchMode = (string)$this->request->getParam('searchMode', 'both');
            if (!in_array($searchMode, ['both', 'name', 'tags'], true)) { $searchMode = 'both'; }
            if (mb_strlen($query) > 120) { return $this->error('Suchbegriff ist zu lang'); }
            $recursive = (string)$this->request->getParam('recursive', '0') === '1';
            $sort = (string)$this->request->getParam('sort', 'name_asc');
            if (!in_array($sort, ['name_asc', 'name_desc', 'newest', 'oldest', 'size_desc'], true)) {
                $sort = 'name_asc';
            }
            $date = (string)$this->request->getParam('date', 'any');
            $size = (string)$this->request->getParam('size', 'any');
            $panoramaOnly = (string)$this->request->getParam('panoramaOnly', '0') === '1';
            if (!in_array($date, ['any', '30d', 'year'], true)) { $date = 'any'; }
            if (!in_array($size, ['any', 'lt10', '10to50', 'gt50'], true)) { $size = 'any'; }
            $threshold = $date === '30d' ? time() - 30 * 86400
                : ($date === 'year' ? strtotime(gmdate('Y') . '-01-01 00:00:00 UTC') : 0);
            $minMb = 10 * 1024 * 1024;
            $maxMb = 50 * 1024 * 1024;
            $folders = [];
            $images = [];
            $pending = [$folder];
            $visited = [];
            $scanned = 0;
            $truncated = false;
            $nonPanoramasExcluded = 0;
            // Bound recursive traversal to keep large/external mounts responsive.
            // Only list readable nodes from the signed-in user's accessible tree.
            while ($pending !== [] && $scanned < 3000 && count($images) < 1200) {
                $current = array_shift($pending);
                $currentId = $current->getId();
                if (isset($visited[$currentId])) { continue; }
                $visited[$currentId] = true;
                try { $listing = $current->getDirectoryListing(); }
                catch (\Throwable $e) { continue; }
                $tagMatches = $searchMode === 'name' ? []
                    : $this->matchingTaggedFileIds($listing, $query, max(0, 3000 - $scanned), $searchMode === 'both');
                foreach ($listing as $node) {
                    if (++$scanned > 3000) { $truncated = true; break 2; }
                    if (!$node->isReadable()) { continue; }
                    $isFolder = $node instanceof Folder;
                    $isImage = $node instanceof File && $node->getMimetype() === 'image/jpeg';
                    if (!$isFolder && !$isImage) { continue; }
                    $relative = $home->getRelativePath($node->getPath());
                    if (!is_string($relative)) { continue; }
                    if ($isFolder) {
                        if ($currentId === $folder->getId()) {
                            $folders[] = ['id' => $node->getId(), 'name' => $node->getName(),
                                'path' => '/' . ltrim($relative, '/'), 'type' => 'folder'];
                        }
                        if ($recursive) { $pending[] = $node; }
                        continue;
                    }
                    if ($query !== '') {
                        $nameMatch = $searchMode !== 'tags' && mb_stripos($node->getName(), $query) !== false;
                        $tagMatch = $searchMode !== 'name' && isset($tagMatches[(string)$node->getId()]);
                        if (!$nameMatch && !$tagMatch) { continue; }
                    }
                    $mtime = (int)$node->getMtime();
                    $bytes = (int)$node->getSize();
                    if ($threshold > 0 && $mtime < $threshold) { continue; }
                    if ($size === 'lt10' && $bytes >= $minMb) { continue; }
                    if ($size === '10to50' && ($bytes < $minMb || $bytes > $maxMb)) { continue; }
                    if ($size === 'gt50' && $bytes <= $maxMb) { continue; }
                    if ($panoramaOnly && !$this->isPanoramaFile($node)) {
                        ++$nonPanoramasExcluded;
                        continue;
                    }
                    $images[] = ['id' => $node->getId(), 'name' => $node->getName(),
                        'path' => '/' . ltrim($relative, '/'), 'type' => 'image',
                        'mtime' => $mtime, 'size' => $bytes];
                    if (count($images) >= 1200) { $truncated = true; break 2; }
                }
                if (!$recursive) { break; }
            }
            if ($pending !== []) { $truncated = true; }
            usort($folders, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
            usort($images, static function ($a, $b) use ($sort): int {
                $name = strnatcasecmp($a['name'], $b['name']);
                return match ($sort) {
                    'name_desc' => -$name,
                    'newest' => ($b['mtime'] <=> $a['mtime']) ?: $name,
                    'oldest' => ($a['mtime'] <=> $b['mtime']) ?: $name,
                    'size_desc' => ($b['size'] <=> $a['size']) ?: $name,
                    default => $name,
                };
            });
            if (count($images) > 400) { $truncated = true; }
            $items = array_merge($folders, array_slice($images, 0, 400));
            $relativeFolder = $home->getRelativePath($folder->getPath());
            $path = '/' . ltrim((string)$relativeFolder, '/');
            $parentId = 0;
            if ($folderId !== 0) {
                $parentPath = trim(dirname('/' . ltrim((string)$relativeFolder, '/')), '/');
                if ($parentPath !== '' && $parentPath !== '.') {
                    $parent = $home->get($parentPath);
                    if ($parent instanceof Folder) { $parentId = $parent->getId(); }
                }
            }
            return new JSONResponse(['success' => true, 'path' => $path, 'folderId' => $folder->getId(),
                'parentId' => $parentId, 'isRoot' => $folder->getId() === $home->getId(), 'items' => $items, 'truncated' => $truncated,
                'panoramaOnly' => $panoramaOnly, 'nonPanoramasExcluded' => $nonPanoramasExcluded,
                'searchMode' => $searchMode]);
        } catch (\Throwable $e) {
            // Fail closed: never expose server paths or exception details to the browser.
            return $this->error('Ordner konnten nicht geladen werden. Bitte Nextcloud-Protokoll prüfen.', 500);
        }
    }

    /** Opaque token lookup; never accept an owner or file path from the public caller. */
    private function shareIndexKey(string $token): string {
        return self::SHARE_INDEX_PREFIX . substr(hash('sha256', $token), 0, 40);
    }

    private function publicTour(string $token): ?array {
        if (!preg_match('/^(?:[a-f0-9]{48}|[a-z0-9][a-z0-9-]{4,62}[a-z0-9])$/D', $token)) { return null; }
        $entry = json_decode($this->config->getAppValue(self::APP_ID, $this->shareIndexKey($token), ''), true);
        if (!is_array($entry) || !isset($entry['uid'], $entry['id'])
            || !is_string($entry['uid']) || !is_string($entry['id'])) { return null; }
        foreach ($this->records($entry['uid']) as $tour) {
            if (!hash_equals((string)$entry['id'], (string)$tour['id'])) { continue; }
            // Existing single links are preserved for backward compatibility.
            if (isset($tour['publicToken']) && is_string($tour['publicToken'])
                && hash_equals($tour['publicToken'], $token)) {
                $expiry = $tour['shareExpiresOn'] ?? null;
                if (is_string($expiry) && $expiry !== '' && gmdate('Y-m-d') > $expiry) { return null; }
                return ['owner' => $entry['uid'], 'tour' => $tour];
            }
            // Per-link credentials must never inherit the legacy link's password or expiry.
            foreach ($tour['shareLinks'] ?? [] as $link) {
                if (!is_array($link) || !isset($link['token']) || !is_string($link['token'])
                    || !hash_equals($link['token'], $token) || !empty($link['disabled'])) { continue; }
                $expiry = $link['expiresOn'] ?? null;
                if (is_string($expiry) && $expiry !== '' && gmdate('Y-m-d') > $expiry) { return null; }
                $selected = $tour;
                $selected['publicToken'] = $link['token'];
                $selected['sharePasswordHash'] = $link['passwordHash'] ?? null;
                $selected['shareExpiresOn'] = $link['expiresOn'] ?? null;
                return ['owner' => $entry['uid'], 'tour' => $selected];
            }
        }
        return null;
    }

    #[NoAdminRequired]
    public function share(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $enabled = (string)$this->request->getParam('enabled', '') === '1';
        // An absent expiry parameter leaves existing settings untouched.
        $expiryRequest = $this->request->getParam('expiresOn', null);
        $expiry = null;
        if ($expiryRequest !== null) {
            if (!is_string($expiryRequest) || ($expiryRequest !== '' &&
                (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $expiryRequest) ||
                !($date = \DateTimeImmutable::createFromFormat('!Y-m-d', $expiryRequest, new \DateTimeZone('UTC'))) ||
                $date->format('Y-m-d') !== $expiryRequest || $expiryRequest < gmdate('Y-m-d')))) {
                return $this->error('Ungültiges Ablaufdatum (heute oder später erforderlich)');
            }
            $expiry = $expiryRequest;
        }
        $rotate = (string)$this->request->getParam('rotate', '0') === '1';
        // A requested custom token is explicit: never silently overwrite someone else's share.
        // Existing 48-hex tokens remain valid and unmodified until the owner changes them.
        $customRequest = $this->request->getParam('customToken', null);
        $customToken = null;
        if ($customRequest !== null) {
            if (!$enabled || $rotate || !is_string($customRequest)) {
                return $this->error('Token-Aktion ungültig');
            }
            $customToken = strtolower(trim($customRequest));
            if (!preg_match('/^[a-z0-9][a-z0-9-]{4,62}[a-z0-9]$/D', $customToken)) {
                return $this->error('Wunsch-Token: 6 bis 64 Zeichen, nur a-z, 0-9 und Bindestriche; Anfang und Ende ohne Bindestrich');
            }
            $existingIndex = $this->config->getAppValue(self::APP_ID, $this->shareIndexKey($customToken), '');
            if ($existingIndex !== '') {
                $existing = json_decode($existingIndex, true);
                if (!is_array($existing) || ($existing['uid'] ?? null) !== $uid || ($existing['id'] ?? null) !== $id) {
                    return $this->error('Dieser Wunsch-Token ist bereits vergeben. Bitte einen anderen wählen.', 409);
                }
            }
        }
        $tours = $this->records($uid);
        foreach ($tours as &$tour) {
            if ($tour['id'] !== $id) { continue; }
            if ($enabled) {
                if (empty($tour['images']) || !is_array($tour['images'])) {
                    return $this->error('Bitte zunächst mindestens ein Panorama zur Tour hinzufügen und speichern');
                }
                foreach ($tour['images'] as $item) {
                    if (!isset($item['id']) || !$this->image($uid, (int)$item['id'])) {
                        return $this->error('Mindestens ein Panorama ist nicht mehr zugänglich', 403);
                    }
                }
                // Set new mapping only for the token belonging to this user's tour.
                $priorToken = isset($tour['publicToken']) && is_string($tour['publicToken']) ? $tour['publicToken'] : null;
                if ($customToken !== null) {
                    $tour['publicToken'] = $customToken;
                } elseif ($rotate || !$priorToken) {
                    $tour['publicToken'] = bin2hex(random_bytes(24));
                }
                if ($expiryRequest !== null) {
                    if ($expiry === null) { unset($tour['shareExpiresOn']); }
                    else { $tour['shareExpiresOn'] = $expiry; }
                }
                $token = $tour['publicToken'];
                $this->config->setAppValue(self::APP_ID, $this->shareIndexKey($token),
                    json_encode(['uid' => $uid, 'id' => $id]));
                if ($priorToken !== null && $priorToken !== $token) {
                    $this->config->deleteAppValue(self::APP_ID, $this->shareIndexKey($priorToken));
                }
            } else {
                if (!empty($tour['publicToken'])) {
                    $this->config->deleteAppValue(self::APP_ID, $this->shareIndexKey($tour['publicToken']));
                }
                unset($tour['publicToken']);
            }
            $tour['modified'] = gmdate('c');
            $this->persist($uid, $tours);
            // Use Nextcloud's registered app route, compatible with all standard deployments.
            // Root-level /t/{token} routes are restricted to core-whitelisted apps in NC 35.
            $url = isset($tour['publicToken'])
                ? $this->urlGenerator->linkToRouteAbsolute('photosphere_photos_integration.tour.publicview', ['token' => $tour['publicToken']])
                : null;
            return new JSONResponse(['success' => true, 'tour' => $this->publicRecord($tour), 'url' => $url]);
        }
        return $this->error('Tour nicht gefunden', 404);
    }

    /** Enforce the same sharing password policy for every independent link. */
    private function validateSharePassword(string $password): ?JSONResponse {
        if ($password === '' || mb_strlen($password) > 128) {
            return $this->error('Passwort muss 1 bis 128 Zeichen enthalten');
        }
        try {
            $this->eventDispatcher->dispatchTyped(new ValidatePasswordPolicyEvent($password, PasswordContext::SHARING));
        } catch (HintException $e) {
            return $this->error($e->getHint() ?: 'Passwort entspricht nicht den Nextcloud-Freigaberichtlinien');
        } catch (\Throwable $e) {
            return $this->error('Passwortrichtlinie konnte nicht geprüft werden', 500);
        }
        return null;
    }

    private function validateShareExpiry(mixed $raw): ?string {
        if (!is_string($raw) || $raw === '') { return $raw === '' ? '' : null; }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw, new \DateTimeZone('UTC'));
        return ($date && $date->format('Y-m-d') === $raw && $raw >= gmdate('Y-m-d')) ? $raw : null;
    }

    /** Additional links are stored inside the user's tour; only this owner can manage them. */
    #[NoAdminRequired]
    public function createShareLink(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $name = trim((string)$this->request->getParam('name', ''));
        if ($name === '' || mb_strlen($name) > 60) { return $this->error('Bezeichnung: 1 bis 60 Zeichen'); }
        $expiry = $this->validateShareExpiry($this->request->getParam('expiresOn', ''));
        if ($expiry === null) { return $this->error('Ungültiges Ablaufdatum'); }
        $password = $this->request->getParam('password', '');
        if (!is_string($password)) { return $this->error('Ungültiges Passwort'); }
        if ($password !== '' && ($error = $this->validateSharePassword($password)) !== null) { return $error; }
        $tours = $this->records($uid);
        foreach ($tours as &$tour) {
            if ($tour['id'] !== $id) { continue; }
            if (empty($tour['images']) || !is_array($tour['images'])) { return $this->error('Bitte zuerst Panoramen hinzufügen'); }
            foreach ($tour['images'] as $item) {
                if (empty($item['id']) || $this->image($uid, (int)$item['id']) === null) {
                    return $this->error('Mindestens ein Panorama ist nicht mehr zugänglich', 403);
                }
            }
            $links = $tour['shareLinks'] ?? [];
            if (!is_array($links) || count($links) >= self::MAX_EXTRA_SHARES) {
                return $this->error('Maximal 12 zusätzliche Links pro Tour');
            }
            $token = bin2hex(random_bytes(24));
            $link = ['id' => bin2hex(random_bytes(8)), 'token' => $token, 'name' => $name,
                'created' => gmdate('c'), 'expiresOn' => $expiry];
            if ($password !== '') { $link['passwordHash'] = password_hash($password, PASSWORD_DEFAULT); }
            $links[] = $link;
            $tour['shareLinks'] = $links;
            $tour['modified'] = gmdate('c');
            $this->persist($uid, $tours);
            $this->config->setAppValue(self::APP_ID, $this->shareIndexKey($token), json_encode(['uid' => $uid, 'id' => $id]));
            return new JSONResponse(['success' => true, 'tour' => $this->publicRecord($tour)]);
        }
        return $this->error('Tour nicht gefunden', 404);
    }

    /** Update one link without changing any of the tour's other public links. */
    #[NoAdminRequired]
    public function updateShareLink(string $id, string $linkId): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $action = (string)$this->request->getParam('action', '');
        if (!in_array($action, ['settings', 'password', 'removePassword', 'disable', 'enable', 'revoke'], true)) {
            return $this->error('Ungültige Aktion');
        }
        $expiry = null;
        $name = null;
        $password = null;
        if ($action === 'settings') {
            $name = trim((string)$this->request->getParam('name', ''));
            $expiry = $this->validateShareExpiry($this->request->getParam('expiresOn', ''));
            if ($name === '' || mb_strlen($name) > 60 || $expiry === null) {
                return $this->error('Ungültige Bezeichnung oder Ablaufdatum');
            }
        }
        if ($action === 'password') {
            $password = $this->request->getParam('password', '');
            if (!is_string($password)) { return $this->error('Ungültiges Passwort'); }
            if (($error = $this->validateSharePassword($password)) !== null) { return $error; }
        }
        $tours = $this->records($uid);
        foreach ($tours as &$tour) {
            if ($tour['id'] !== $id) { continue; }
            if (!isset($tour['shareLinks']) || !is_array($tour['shareLinks'])) { return $this->error('Freigabelink nicht gefunden', 404); }
            foreach ($tour['shareLinks'] as $i => &$link) {
                if (!is_array($link) || ($link['id'] ?? '') !== $linkId) { continue; }
                if ($action === 'revoke') {
                    $token = (string)$link['token'];
                    unset($tour['shareLinks'][$i]);
                    $tour['shareLinks'] = array_values($tour['shareLinks']);
                    $this->persist($uid, $tours);
                    $this->config->deleteAppValue(self::APP_ID, $this->shareIndexKey($token));
                } else {
                    if ($action === 'settings') { $link['name'] = $name; $link['expiresOn'] = $expiry; }
                    if ($action === 'password') { $link['passwordHash'] = password_hash($password, PASSWORD_DEFAULT); }
                    if ($action === 'removePassword') { unset($link['passwordHash']); }
                    if ($action === 'disable') { $link['disabled'] = true; }
                    if ($action === 'enable') {
                        if (!empty($link['expiresOn']) && $link['expiresOn'] < gmdate('Y-m-d')) {
                            return $this->error('Vor dem Aktivieren bitte das Ablaufdatum aktualisieren');
                        }
                        foreach ($tour['images'] ?? [] as $item) {
                            if (empty($item['id']) || $this->image($uid, (int)$item['id']) === null) {
                                return $this->error('Mindestens ein Panorama ist nicht mehr zugänglich', 403);
                            }
                        }
                        unset($link['disabled']);
                    }
                    $tour['modified'] = gmdate('c');
                    $this->persist($uid, $tours);
                }
                return new JSONResponse(['success' => true, 'tour' => $this->publicRecord($tour)]);
            }
            unset($link);
            return $this->error('Freigabelink nicht gefunden', 404);
        }
        return $this->error('Tour nicht gefunden', 404);
    }

    /** Owner-only password changes. Changing the password revokes prior visitor sessions. */
    #[NoAdminRequired]
    public function sharePassword(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $action = (string)$this->request->getParam('action', '');
        if (!in_array($action, ['set', 'remove'], true)) { return $this->error('Ungültige Passwort-Aktion'); }
        $password = $this->request->getParam('password', '');
        if ($action === 'set') {
            if (!is_string($password) || $password === '' || mb_strlen($password) > 128) {
                return $this->error('Passwort darf nicht leer sein und höchstens 128 Zeichen enthalten');
            }
            try {
                // Same policy context as the regular Nextcloud public-link shares.
                $this->eventDispatcher->dispatchTyped(new ValidatePasswordPolicyEvent($password, PasswordContext::SHARING));
            } catch (HintException $e) {
                return $this->error($e->getHint() ?: 'Passwort entspricht nicht den Nextcloud-Freigaberichtlinien');
            } catch (\Throwable $e) {
                // Never skip policy checks silently when validation fails unexpectedly.
                return $this->error('Passwortrichtlinie konnte nicht geprüft werden', 500);
            }
        }
        $tours = $this->records($uid);
        foreach ($tours as &$tour) {
            if ($tour['id'] !== $id) { continue; }
            if (empty($tour['publicToken'])) { return $this->error('Zuerst einen Freigabelink erstellen'); }
            if ($action === 'set') {
                $tour['sharePasswordHash'] = password_hash($password, PASSWORD_DEFAULT);
            } else {
                // Only remove the password protecting the legacy tour link.
                // Independent share links keep their own password hashes intact.
                // Hashes are removed solely from serialized API responses in publicRecord().
                unset($tour['sharePasswordHash']);
            }
            $tour['modified'] = gmdate('c');
            $this->persist($uid, $tours);
            return new JSONResponse(['success' => true, 'tour' => $this->publicRecord($tour)]);
        }
        return $this->error('Tour nicht gefunden', 404);
    }

    /** Public visitor unlock: server-side session; no authorization in URLs or local storage. */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicUnlock(string $token): Response {
        $path = $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicView', ['token' => $token]);
        $found = $this->publicTour($token);
        if ($found === null || empty($found['tour']['sharePasswordHash'])) {
            return new RedirectResponse($path, 303);
        }
        $ip = $this->request->getRemoteAddress();
        $action = 'photosphere_tour_password';
        $this->throttler->sleepDelayOrThrowOnMax($ip, $action);
        $password = $this->request->getParam('password', '');
        if (is_string($password) && strlen($password) <= 512 && password_verify($password, $found['tour']['sharePasswordHash'])) {
            $key = 'ppi_tour_unlock_' . hash('sha256', $token);
            $this->session->set($key, hash('sha256', $token . '|' . $found['tour']['sharePasswordHash']));
            $this->session->remove('ppi_tour_error_' . hash('sha256', $token));
        } else {
            $this->throttler->registerAttempt($action, $ip);
            $this->session->set('ppi_tour_error_' . hash('sha256', $token), true);
        }
        return new RedirectResponse($path, 303);
    }

    /** Add escaped Open Graph/Twitter metadata to Nextcloud's page head. */
    private function socialTags(string $title, string $description, string $imageUrl, string $pageUrl): void {
        foreach ([
            ['property' => 'og:type', 'content' => 'website'],
            ['property' => 'og:title', 'content' => $title],
            ['property' => 'og:description', 'content' => $description],
            ['property' => 'og:url', 'content' => $pageUrl],
            ['property' => 'og:image', 'content' => $imageUrl],
            ['property' => 'og:image:width', 'content' => '1200'],
            ['property' => 'og:image:height', 'content' => '630'],
            ['name' => 'twitter:card', 'content' => 'summary_large_image'],
            ['name' => 'twitter:title', 'content' => $title],
            ['name' => 'twitter:description', 'content' => $description],
            ['name' => 'twitter:image', 'content' => $imageUrl],
        ] as $attributes) {
            Util::addHeader('meta', $attributes);
        }
    }

    /** Resolve only an image explicitly belonging to this public tour and still readable by its owner. */
    private function socialCoverFile(array $found): ?File {
        $tour = $found['tour'];
        $images = $tour['images'] ?? [];
        if (!is_array($images) || !$images) { return null; }
        $startId = (int)($tour['display']['startImageId'] ?? 0);
        $ids = [];
        foreach ($images as $item) {
            if (is_array($item) && isset($item['id']) && (int)$item['id'] > 0) { $ids[] = (int)$item['id']; }
        }
        if (!$ids) { return null; }
        if (!in_array($startId, $ids, true)) { $startId = $ids[0]; }
        if ($this->image($found['owner'], $startId) === null) { return null; }
        try {
            $home = $this->rootFolder->getUserFolder($found['owner']);
            foreach ($home->getById($startId) as $node) {
                if ($node instanceof File && $node->isReadable()
                    && $node->getMimetype() === 'image/jpeg'
                    && is_string($home->getRelativePath($node->getPath()))) { return $node; }
            }
        } catch (\Throwable $e) { /* Do not disclose internal paths. */ }
        return null;
    }

    /** A public OG cover is a deliberately shared derivative, never the original panorama. */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function socialCover(string $token): Response {
        $found = $this->publicTour($token);
        if ($found === null) { return $this->error('Vorschau nicht verfügbar', 404); }
        $mode = (string)($found['tour']['display']['socialPreviewMode'] ?? 'cover');
        if ($mode !== 'cover' || !empty($found['tour']['sharePasswordHash'])) {
            return $this->error('Vorschau nicht verfügbar', 404);
        }
        $file = $this->socialCoverFile($found);
        if ($file === null) { return $this->error('Vorschau nicht verfügbar', 404); }
        try {
            $preview = $this->previewManager->getPreview($file, 1200, 630, true);
            $response = new FileDisplayResponse($preview, 200, ['Content-Type' => $preview->getMimeType()]);
            $response->addHeader('Cache-Control', 'public, max-age=300');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
            return $response;
        } catch (\Throwable $e) { return $this->error('Vorschau nicht verfügbar', 404); }
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicView(string $token): Response {
        if ($this->publicTour($token) === null) {
            // Render a standalone error in the server template; the public viewer JS is
            // intentionally not registered for revoked, expired or unknown tokens.
            $response = new TemplateResponse(self::APP_ID, 'tour-public', ['invalid' => true, 'token' => ''], 'guest');
            $response->setStatus(404);
            return $response;
        }
        $found = $this->publicTour($token);
        $tour = $found['tour'];
        $mode = (string)($tour['display']['socialPreviewMode'] ?? 'cover');
        if (!in_array($mode, ['cover', 'neutral', 'off'], true)) { $mode = 'cover'; }
        if ($mode !== 'off') {
            $protected = !empty($tour['sharePasswordHash']);
            $fallbackImage = $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->imagePath(self::APP_ID, 'social-tour-neutral.png')
            );
            $previewImage = $fallbackImage;
            $title = '360°-Tour';
            $description = 'Interaktive 360°-Tour';
            if (!$protected) {
                $title = mb_substr(trim((string)($tour['name'] ?? '360°-Tour')), 0, 100);
                $description = trim((string)($tour['display']['introText'] ?? ''));
                // Social previews are plain text, not raw Markdown or icon shortcodes.
                $description = preg_replace('/\[([^\]]+)\]\(https?:\/\/[^\s)]+\)/i', '$1', $description) ?? $description;
                $description = str_replace([':quad:', ':drone:', ':360:'], ['Quad/ATV', 'Drohne', '360°'], $description);
                $description = preg_replace('/(?:^|\n)\s*(?:[-*]|\d+[.)])\s+/m', ' ', $description) ?? $description;
                $description = mb_substr(trim(preg_replace('/\s+/u', ' ', $description) ?? $description), 0, 250);
                if ($description === '') { $description = 'Entdecke diese interaktive 360°-Tour.'; }
                if ($mode === 'cover' && $this->socialCoverFile($found) !== null) {
                    $previewImage = $this->urlGenerator->getAbsoluteURL(
                        $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.socialCover', ['token' => $token])
                    );
                }
            } else {
                $title = 'Passwortgeschützte 360°-Tour';
                $description = 'Diese 360°-Tour ist mit einem Passwort geschützt.';
            }
            $this->socialTags($title, $description, $previewImage, $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicView', ['token' => $token])
            ));
        }
        if (!empty($found['tour']['sharePasswordHash']) && !$this->unlocked($token, $found['tour'])) {
            $errorKey = 'ppi_tour_error_' . hash('sha256', $token);
            $passwordError = (bool)$this->session->get($errorKey);
            $this->session->remove($errorKey);
            $response = new TemplateResponse(self::APP_ID, 'tour-public', [
                'invalid' => false, 'passwordRequired' => true, 'passwordError' => $passwordError,
                'unlockUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicUnlock', ['token' => $token]),
            ], 'guest');
            $response->addHeader('Cache-Control', 'no-store');
            return $response;
        }
        $this->queuePanoramas($found['owner'], $tour['images'] ?? []);
        Util::addScript(self::APP_ID, 'tour-sphere');
        Util::addScript(self::APP_ID, 'tour-public');
        $response = new TemplateResponse(self::APP_ID, 'tour-public', ['invalid' => false, 'token' => $token,
            'manifestUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicManifest', ['token' => $token]),
            'thumbUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicThumbnail', ['token' => $token, 'fileId' => 0]),
            'imageUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicImage', ['token' => $token, 'fileId' => 0]),
            'optimizedImageUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicOptimizedImage', ['token' => $token, 'fileId' => 0, 'profile' => 'desktop']),
            'watermarkUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicWatermark', ['token' => $token]),
            'watermarkOptions' => json_encode(json_decode($this->config->getAppValue(self::APP_ID, 'watermark_options', '{}'), true) ?: [])], 'guest');
        $response->addHeader('Cache-Control', 'no-store');
        return $response;
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicManifest(string $token): JSONResponse {
        $found = $this->authorizedTour($token);
        if ($found === null) { return $this->error('Tour nicht verfügbar', 404); }
        $tour = $found['tour'];
        // Each image is checked against the owner's currently readable file tree.
        $images = [];
        foreach ($tour['images'] ?? [] as $item) {
            if (!isset($item['id'])) { continue; }
            $valid = $this->image($found['owner'], (int)$item['id']);
            if ($valid !== null) { $images[] = ['id' => $valid['id'], 'name' => $valid['name']]; }
        }
        $ids = array_fill_keys(array_map(fn($x) => (string)$x['id'], $images), true);
        $links = [];
        foreach ($tour['links'] ?? [] as $link) {
            $from = (string)($link['from'] ?? '');
            $to = (string)($link['to'] ?? '');
            if (isset($ids[$from], $ids[$to])) {
                $links[] = ['from' => (int)$from, 'to' => (int)$to,
                    'yaw' => (float)($link['yaw'] ?? 0), 'pitch' => (float)($link['pitch'] ?? -0.10)];
            }
        }
        if (!empty($tour['display']['watermarkEnabled']) && !$this->tourWatermarkAllowed((string)($tour['display']['watermarkId'] ?? 'none'))) {
            $tour['display']['watermarkEnabled'] = false;
        }
        $response = new JSONResponse(['success' => true, 'tour' => [
            'name' => (string)$tour['name'], 'images' => $images, 'links' => $links, 'display' => $tour['display'] ?? []]]);
        $response->addHeader('Cache-Control', 'no-store');
        return $response;
    }

    /** Public, token-scoped preview (small navigation card or bounded landing cover). */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicThumbnail(string $token, string $fileId): Response {
        $found = $this->authorizedTour($token);
        if ($found === null || !ctype_digit($fileId) || (int)$fileId <= 0) {
            return $this->error('Vorschau nicht verfügbar', 404);
        }
        $id = (int)$fileId;
        $allowed = false;
        foreach ($found['tour']['images'] ?? [] as $item) {
            if ((int)($item['id'] ?? 0) === $id) { $allowed = true; break; }
        }
        if (!$allowed || $this->image($found['owner'], $id) === null) {
            return $this->error('Vorschau nicht verfügbar', 404);
        }
        try {
            $home = $this->rootFolder->getUserFolder($found['owner']);
            foreach ($home->getById($id) as $node) {
                if (!$node instanceof File || !$node->isReadable() || $node->getMimetype() !== 'image/jpeg') { continue; }
                if (!is_string($home->getRelativePath($node->getPath()))) { continue; }
                // The landing page needs a sharp but bounded image; navigation cards stay small.
                $isCover = (string)$this->request->getParam('cover', '') === '1';
                $preview = $this->previewManager->getPreview($node, $isCover ? 1600 : 320, $isCover ? 900 : 180, true);
                $response = new FileDisplayResponse($preview, 200, ['Content-Type' => $preview->getMimeType()]);
                $response->addHeader('Cache-Control', 'no-store');
                $response->addHeader('X-Content-Type-Options', 'nosniff');
                return $response;
            }
        } catch (\Throwable $e) { /* No fallback to original full-resolution image. */ }
        return $this->error('Vorschau nicht verfügbar', 404);
    }

    /**
     * Token-scoped 2:1 panorama derivative. Nextcloud's preview provider creates
     * and caches the output; originals stay untouched. Never expose a raw preview
     * cache URL or bypass tour/password authorisation. A preview provider may
     * impose its own maximum dimensions. If unavailable, fall back to the
     * authorised original stream rather than breaking the viewer.
     */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicOptimizedImage(string $token, string $fileId, string $profile): Response {
        if (!in_array($profile, ['mobile', 'desktop'], true)) {
            return $this->error('Bild nicht verfügbar', 404);
        }
        $found = $this->authorizedTour($token);
        if ($found === null || !ctype_digit($fileId) || (int)$fileId <= 0) {
            return $this->error('Bild nicht verfügbar', 404);
        }
        $id = (int)$fileId;
        $allowed = false;
        foreach ($found['tour']['images'] ?? [] as $entry) {
            if ((int)($entry['id'] ?? 0) === $id) { $allowed = true; break; }
        }
        if (!$allowed || $this->image($found['owner'], $id) === null) {
            return $this->error('Bild nicht verfügbar', 404);
        }
        try {
            $home = $this->rootFolder->getUserFolder($found['owner']);
            foreach ($home->getById($id) as $node) {
                if (!$node instanceof File || !$node->isReadable() || $node->getMimetype() !== 'image/jpeg') { continue; }
                if (!is_string($home->getRelativePath($node->getPath()))) { continue; }
                // Isolated appdata derivative, produced by Nextcloud background jobs.
                // Authorization above is mandatory even for cached bytes.
                $derivative = $this->panoramaCache->cached($node, $profile);
                if ($derivative !== null) {
                    $response = new FileDisplayResponse($derivative, 200, ['Content-Type' => 'image/jpeg']);
                    $response->addHeader('Cache-Control', 'private, no-store');
                    $response->addHeader('X-Content-Type-Options', 'nosniff');
                    return $response;
                }
                // Cache not yet ready or host lacks Imagick: continue using the
                // original Nextcloud preview path, never breaking a public tour.
                $width = $profile === 'mobile' ? 4096 : 8192;
                $height = intdiv($width, 2);
                // false: do not crop away panorama edges (critical for 360° wrapping).
                $preview = $this->previewManager->getPreview($node, $width, $height, false);
                if ($preview->getSize() <= 0 || !str_starts_with($preview->getMimeType(), 'image/')) { break; }
                $response = new FileDisplayResponse($preview, 200, ['Content-Type' => $preview->getMimeType()]);
                $response->addHeader('Cache-Control', 'private, no-store');
                $response->addHeader('X-Content-Type-Options', 'nosniff');
                return $response;
            }
        } catch (\Throwable $e) {
            // Preview generation may be disabled or fail on very large JPEGs.
            // Existing, separately authorised full-image route remains available.
        }
        return $this->publicImage($token, $fileId);
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicImage(string $token, string $fileId): Response {
        $found = $this->authorizedTour($token);
        if ($found === null || !ctype_digit($fileId) || (int)$fileId <= 0) {
            return $this->error('Bild nicht verfügbar', 404);
        }
        $id = (int)$fileId;
        $allowed = false;
        foreach ($found['tour']['images'] ?? [] as $entry) {
            if ((int)($entry['id'] ?? 0) === $id) { $allowed = true; break; }
        }
        if (!$allowed || $this->image($found['owner'], $id) === null) {
            return $this->error('Bild nicht verfügbar', 404);
        }
        try {
            $home = $this->rootFolder->getUserFolder($found['owner']);
            foreach ($home->getById($id) as $node) {
                if (!$node instanceof File || !$node->isReadable() || $node->getMimetype() !== 'image/jpeg') { continue; }
                if (!is_string($home->getRelativePath($node->getPath()))) { continue; }
                $stream = $node->fopen('r');
                if (!is_resource($stream)) { break; }
                $response = new StreamResponse($stream);
                $response->addHeader('Content-Type', 'image/jpeg');
                $response->addHeader('Cache-Control', 'no-store');
                $response->addHeader('X-Content-Type-Options', 'nosniff');
                return $response;
            }
        } catch (\Throwable $e) { /* Do not expose owner file paths. */ }
        return $this->error('Bild nicht verfügbar', 404);
    }

    #[NoAdminRequired]
    public function save(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $tours = $this->records($uid);
        foreach ($tours as &$tour) {
            if ($tour['id'] !== $id) { continue; }
            $name = trim((string)$this->request->getParam('name', $tour['name']));
            if ($name === '' || mb_strlen($name) > 100) { return $this->error('Ungültiger Tourname'); }
            $rawImages = $this->request->getParam('images', '[]');
            $rawLinks = $this->request->getParam('links', '[]');
            $images = is_string($rawImages) ? json_decode($rawImages, true) : $rawImages;
            $links = is_string($rawLinks) ? json_decode($rawLinks, true) : $rawLinks;
            if (!is_array($images) || !array_is_list($images) || count($images) > self::MAX_IMAGES
                || !is_array($links) || !array_is_list($links) || count($links) > 300) {
                return $this->error('Ungültige Tourdaten oder Mengenlimit überschritten');
            }
            $verified = [];
            foreach ($images as $image) {
                if (!is_array($image) || !isset($image['id']) || !filter_var($image['id'], FILTER_VALIDATE_INT)) {
                    return $this->error('Ungültige Bildauswahl');
                }
                $node = $this->image($uid, (int)$image['id']);
                if ($node === null) { return $this->error('Mindestens ein Bild ist nicht mehr zugänglich', 403); }
                $key = (string)$node['id'];
                $verified[$key] = $node;
            }
            $verifiedLinks = [];
            foreach ($links as $link) {
                if (!is_array($link) || !isset($link['from'], $link['to'])) { return $this->error('Ungültige Verknüpfung'); }
                $from = (string)$link['from']; $to = (string)$link['to'];
                if ($from === $to || !isset($verified[$from], $verified[$to])) { return $this->error('Verknüpfung verweist auf ungültiges Bild'); }
                $pair = $from . ':' . $to;
                $yaw = filter_var($link['yaw'] ?? 0, FILTER_VALIDATE_FLOAT);
                $pitch = filter_var($link['pitch'] ?? -0.10, FILTER_VALIDATE_FLOAT);
                if ($yaw === false || $pitch === false || !is_finite((float)$yaw) || !is_finite((float)$pitch)
                    || abs((float)$yaw) > 100000 || (float)$pitch < -1.55 || (float)$pitch > 1.55) {
                    return $this->error('Ungültige Position eines Navigationspunkts');
                }
                $verifiedLinks[$pair] = ['from' => (int)$from, 'to' => (int)$to,
                    'yaw' => (float)$yaw, 'pitch' => (float)$pitch];
            }
            $rawDisplay = $this->request->getParam('display', null);
            if ($rawDisplay !== null) {
                $input = is_string($rawDisplay) ? json_decode($rawDisplay, true) : $rawDisplay;
                if (!is_array($input)) { return $this->error('Ungültige Darstellungsoptionen'); }
                $startId = filter_var($input['startImageId'] ?? 0, FILTER_VALIDATE_INT);
                $startYaw = filter_var($input['startYaw'] ?? 0, FILTER_VALIDATE_INT);
                $description = trim((string)($input['introText'] ?? ''));
                if (mb_strlen($description) > 1200) { return $this->error('Beschreibung: maximal 1.200 Zeichen'); }
                $size = (string)($input['hotspotSize'] ?? 'medium');
                // Preserve older tours: replace the retired chat pin when display is saved.
                $style = (string)($input['hotspotStyle'] ?? 'classic');
                if ($style === 'chat-pin') { $style = 'map-pin'; }
                $svgPinColor = (string)($input['svgPinColor'] ?? ($tour['display']['svgPinColor'] ?? '#1687bc'));
                $socialMode = (string)($input['socialPreviewMode'] ?? ($tour['display']['socialPreviewMode'] ?? 'cover'));
                $watermarkEnabled = filter_var($input['watermarkEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $watermarkId = (string)($input['watermarkId'] ?? 'none');
                if ($watermarkEnabled && !$this->tourWatermarkAllowed($watermarkId)) {
                    return $this->error('Wasserzeichen nicht für Touren freigegeben', 403);
                }
                if ($startId === false || ($startId !== 0 && !isset($verified[(string)$startId])) ||
                    $startYaw === false || $startYaw < -180 || $startYaw > 180 ||
                    !in_array($size, ['xsmall','small','medium','large','xlarge'], true) ||
                    !in_array($style, ['classic','blue','map-pin','label-pin','outline-pin'], true) ||
                    !preg_match('/^#[0-9a-fA-F]{6}$/D', $svgPinColor) ||
                    !in_array($socialMode, ['cover','neutral','off'], true)) {
                    return $this->error('Ungültige Startansicht oder Hotspot-Größe');
                }
                $tour['display'] = [
                    'startImageId' => $startId, 'startYaw' => $startYaw,
                    'introText' => $description,
                    'hotspotSize' => $size,
                    'hotspotStyle' => $style,
                    'svgPinColor' => strtolower($svgPinColor),
                    'socialPreviewMode' => $socialMode,
                    'showTitle' => filter_var($input['showTitle'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'showNavigation' => filter_var($input['showNavigation'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'showFullscreen' => filter_var($input['showFullscreen'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'watermarkEnabled' => $watermarkEnabled,
                    'watermarkId' => $watermarkEnabled ? $watermarkId : 'none',
                ];
            }
            $tour['name'] = $name;
            $tour['images'] = array_values($verified);
            $tour['links'] = array_values($verifiedLinks);
            $tour['modified'] = gmdate('c');
            $this->persist($uid, $tours);
            $this->queuePanoramas($uid, $tour['images']);
            return new JSONResponse(['success' => true, 'tour' => $this->publicRecord($tour)]);
        }
        return $this->error('Tour nicht gefunden', 404);
    }

    #[NoAdminRequired]
    public function delete(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $tours = $this->records($uid);
        foreach ($tours as $tour) {
            if ($tour['id'] === $id) {
                if (!empty($tour['publicToken'])) {
                    $this->config->deleteAppValue(self::APP_ID, $this->shareIndexKey($tour['publicToken']));
                }
                foreach ($tour['shareLinks'] ?? [] as $link) {
                    if (is_array($link) && !empty($link['token'])) {
                        $this->config->deleteAppValue(self::APP_ID, $this->shareIndexKey((string)$link['token']));
                    }
                }
            }
        }
        $filtered = array_values(array_filter($tours, fn($t) => $t['id'] !== $id));
        if (count($filtered) === count($tours)) { return $this->error('Tour nicht gefunden', 404); }
        $this->persist($uid, $filtered);
        return new JSONResponse(['success' => true]);
    }
}
