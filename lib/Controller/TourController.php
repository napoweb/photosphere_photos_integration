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
    private const STARTPAGE_INDEX_PREFIX = 'startpub_';

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
        $tour['shareExpiryTimezone'] = $this->shareExpiryZone($this->userId() ?? '')->getName();
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

    /** A start-page grant is a session capability, never a query parameter or permanent tour unlock.
     * Every use rechecks start-page validity, its password session, the selected tour and
     * the exact live tour-share token (including revocation/expiry via publicTour()).
     */
    private function startPageTourGranted(string $token, array $found): bool {
        if (empty($found['tour']['sharePasswordHash'])) { return false; }
        $grant = $this->session->get('ppi_startpage_tour_grant_' . hash('sha256', $token));
        if (!is_string($grant) || !preg_match('/^[a-f0-9]{48}$/D', $grant)) { return false; }
        $start = $this->findPublicStartPage($grant);
        if ($start === null || $start['owner'] !== $found['owner'] ||
            empty($start['page']['sharePasswordHash']) ||
            !$this->startPageUnlocked($grant, $start['page'])) { return false; }
        $page = $start['page'];
        $mode = $page['tourPasswordMode'] ?? 'keep';
        $tourId = (string)$found['tour']['id'];
        if (!in_array($tourId, $page['tourIds'] ?? [], true) ||
            !($mode === 'all' || ($mode === 'selected' &&
                in_array($tourId, $page['tourPasswordSelected'] ?? [], true)))) { return false; }
        // Do not extend a start-page grant to a different independent share link.
        $ownerTours = $this->records($start['owner']);
        foreach ($ownerTours as $tour) {
            if (($tour['id'] ?? null) !== $tourId) { continue; }
            return $this->startPagePreferredTourToken($start['owner'], $tour, $page) === $token;
        }
        return false;
    }

    /** A start page pins exactly one link per tour; unavailable means unavailable.
     * Legacy pages without a binding use MAIN only, never a secondary fallback.
     * Shared by tile generation and every subsequent start-page session grant.
     */
    private function startPagePreferredTourToken(string $owner, array $tour, array $page): ?string {
        $tourId = (string)($tour['id'] ?? '');
        $binding = $page['tourShareBindings'][$tourId] ?? 'main';
        $candidate = null;
        if ($binding === 'main') {
            $candidate = $tour['publicToken'] ?? null;
        } elseif (is_string($binding) && preg_match('/^share:[a-f0-9]{16}$/D', $binding)) {
            $id = substr($binding, 6);
            foreach ($tour['shareLinks'] ?? [] as $link) {
                if (is_array($link) && ($link['id'] ?? '') === $id) {
                    $candidate = $link['token'] ?? null;
                    break;
                }
            }
        }
        if (!is_string($candidate) || $candidate === '') { return null; }
        $resolved = $this->publicTour($candidate);
        if ($resolved === null || ($resolved['owner'] ?? null) !== $owner ||
            ($resolved['tour']['id'] ?? null) !== $tourId) { return null; }
        return $candidate;
    }

    private function authorizedTour(string $token): ?array {
        $found = $this->publicTour($token);
        if ($found === null || (!$this->unlocked($token, $found['tour']) &&
            !$this->startPageTourGranted($token, $found))) { return null; }
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

    /** Owner endpoints never include password hashes. */
    private function startPagePublicRecord(array $page): array {
        $page['passwordProtected'] = !empty($page['sharePasswordHash']);
        $page['shareExpiryTimezone'] = $this->shareExpiryZone($this->userId() ?? '')->getName();
        unset($page['sharePasswordHash']);
        // PPI STARTPAGE MULTISHARE V1 2026-11-24: never leak hashes.
        if (isset($page['shareLinks']) && is_array($page['shareLinks'])) {
            $page['shareLinks'] = array_values(array_map(static function (array $link): array {
                $link['passwordProtected'] = !empty($link['passwordHash']);
                unset($link['passwordHash']);
                return $link;
            }, array_filter($page['shareLinks'], 'is_array')));
        }
        return $page;
    }

    private function startPageIndexKey(string $token): string {
        // oc_appconfig.configkey supports at most 64 chars on supported Nextcloud databases.
        // Prefix (9) + truncated SHA-256 (40) matches the established tour share index.
        return self::STARTPAGE_INDEX_PREFIX . substr(hash('sha256', $token), 0, 40);
    }

    /** PPI STARTPAGE MULTISHARE V1 2026-11-24.
     * Resolve only indexed and currently active tokens. Independent shares get their
     * own hash and expiry; neither a revoked share nor another share's session grants access.
     */
    private function findPublicStartPage(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{48}$/D', $token)) { return null; }
        $index = json_decode($this->config->getAppValue(self::APP_ID, $this->startPageIndexKey($token), ''), true);
        if (!is_array($index) || !isset($index['uid'], $index['id']) ||
            !is_string($index['uid']) || !is_string($index['id'])) { return null; }
        foreach ($this->startPageRecords($index['uid']) as $page) {
            if (!hash_equals((string)$page['id'], $index['id'])) { continue; }
            if (isset($page['publicToken']) && is_string($page['publicToken']) &&
                hash_equals($page['publicToken'], $token)) {
                if (!empty($page['shareExpiresOn']) && $this->shareExpiryReached($page['shareExpiresOn'], $index['uid'])) { return null; }
                return ['owner' => $index['uid'], 'page' => $page];
            }
            foreach ($page['shareLinks'] ?? [] as $link) {
                if (!is_array($link) || !isset($link['token']) || !is_string($link['token']) ||
                    !hash_equals($link['token'], $token)) { continue; }
                if (!empty($link['disabled']) || (!empty($link['expiresOn']) && $this->shareExpiryReached($link['expiresOn'], $index['uid']))) { return null; }
                // PPI STARTPAGE PER-LINK POLICY V26.3: no inheritance from main share.
                if (empty($link['passwordHash'])) { unset($page['sharePasswordHash']); }
                else { $page['sharePasswordHash'] = $link['passwordHash']; }
                $page['shareExpiresOn'] = $link['expiresOn'] ?? '';
                $page['tourPasswordMode'] = in_array($link['tourPasswordMode'] ?? 'keep', ['keep','all','selected'], true)
                    ? $link['tourPasswordMode'] ?? 'keep' : 'keep';
                $page['tourPasswordSelected'] = is_array($link['tourPasswordSelected'] ?? null)
                    ? $link['tourPasswordSelected'] : [];
                $page['showPasswordProtectedTours'] = ($link['showPasswordProtectedTours'] ?? true) !== false;
                // A passwordless start page MUST NEVER unlock protected tour links.
                if (empty($page['sharePasswordHash'])) {
                    $page['tourPasswordMode'] = 'keep';
                    $page['tourPasswordSelected'] = [];
                }
                return ['owner' => $index['uid'], 'page' => $page];
            }
            return null;
        }
        return null;
    }

    private function startPageUnlocked(string $token, array $page): bool {
        if (empty($page['sharePasswordHash'])) { return true; }
        $actual = $this->session->get('ppi_startpage_unlock_' . hash('sha256', $token));
        $expected = hash('sha256', $token . '|' . $page['sharePasswordHash']);
        return is_string($actual) && hash_equals($actual, $expected);
    }

    private function persistStartPages(string $uid, array $pages): void {
        $this->config->setUserValue($uid, self::APP_ID, 'tour_start_pages',
            json_encode(array_values($pages), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    #[NoAdminRequired]
    public function listStartPages(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        return new JSONResponse(['success' => true, 'pages' => array_map(fn($p) => $this->startPagePublicRecord($p), $this->startPageRecords($uid))]);
    }

    /** Personal, design-only presets. No sharing credentials or tour references. */
    private const PRESET_FIELDS = ['layout', 'designEffect', 'tileColumns', 'headingColor',
        'descriptionColor', 'backgroundColor', 'headerBackgroundColor', 'backgroundFileId'];

    private function startPagePresets(string $uid): array {
        $raw = json_decode($this->config->getUserValue($uid, self::APP_ID, 'startpage_design_presets', '[]'), true);
        return is_array($raw) ? array_values(array_filter($raw, static fn($p) =>
            is_array($p) && is_string($p['id'] ?? null) && is_string($p['name'] ?? null)
            && is_array($p['design'] ?? null))) : [];
    }

    private function validPresetDesign(mixed $raw, string $uid): ?array {
        if (!is_array($raw)) { return null; }
        $design = [];
        foreach (self::PRESET_FIELDS as $field) {
            if (!array_key_exists($field, $raw)) { return null; }
            $design[$field] = $raw[$field];
        }
        if (!in_array($design['layout'], ['hero','split','gallery'], true)
            || !in_array($design['designEffect'], ['subtle','glass','glossy'], true)
            || !in_array($design['tileColumns'], ['auto','2','3','4','5'], true)) { return null; }
        foreach (['headingColor','descriptionColor','backgroundColor','headerBackgroundColor'] as $color) {
            if (!is_string($design[$color]) || !preg_match('/^#[0-9a-fA-F]{6}$/D', $design[$color])) { return null; }
        }
        $id = filter_var($design['backgroundFileId'], FILTER_VALIDATE_INT);
        if ($id === false || $id < 0 || ($id > 0 && $this->image($uid, $id) === null)) { return null; }
        $design['backgroundFileId'] = $id;
        return $design;
    }

    #[NoAdminRequired]
    public function listStartPagePresets(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        return new JSONResponse(['success' => true, 'presets' => $this->startPagePresets($uid)]);
    }

    #[NoAdminRequired]
    public function saveStartPagePreset(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $name = trim((string)$this->request->getParam('name', ''));
        $id = (string)$this->request->getParam('id', '');
        $raw = (string)$this->request->getParam('design', '');
        if ($name === '' || mb_strlen($name) > 80 || strlen($raw) > 4096) {
            return $this->error('Ungültiger Preset-Name oder zu große Vorlage');
        }
        $design = $this->validPresetDesign(json_decode($raw, true), $uid);
        if ($design === null) { return $this->error('Ungültiges Design oder Hintergrundbild nicht verfügbar', 400); }
        $presets = $this->startPagePresets($uid);
        if ($id !== '') {
            foreach ($presets as &$preset) {
                if ($preset['id'] !== $id) { continue; }
                $preset['name'] = $name;
                $preset['design'] = $design;
                $preset['modified'] = gmdate('c');
                $this->config->setUserValue($uid, self::APP_ID, 'startpage_design_presets', json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return new JSONResponse(['success' => true, 'preset' => $preset]);
            }
            return $this->error('Preset nicht gefunden', 404);
        }
        if (count($presets) >= 30) { return $this->error('Maximal 30 eigene Presets'); }
        $preset = ['id' => bin2hex(random_bytes(12)), 'name' => $name, 'design' => $design, 'modified' => gmdate('c')];
        $presets[] = $preset;
        $this->config->setUserValue($uid, self::APP_ID, 'startpage_design_presets', json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return new JSONResponse(['success' => true, 'preset' => $preset]);
    }

    #[NoAdminRequired]
    public function deleteStartPagePreset(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $presets = $this->startPagePresets($uid);
        $remaining = array_values(array_filter($presets, static fn($preset) => $preset['id'] !== $id));
        if (count($presets) === count($remaining)) { return $this->error('Preset nicht gefunden', 404); }
        $this->config->setUserValue($uid, self::APP_ID, 'startpage_design_presets', json_encode($remaining, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return new JSONResponse(['success' => true]);
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
            'headerBackgroundColor' => '#000000',
            'designEffect' => 'subtle',
            'backgroundFileId' => 0, 'tileColumns' => 'auto', 'mobileColumns' => '2',
            'tourIds' => [], 'created' => gmdate('c'), 'modified' => gmdate('c')];
        $pages[] = $page;
        $this->persistStartPages($uid, $pages);
        return new JSONResponse(['success' => true, 'page' => $this->startPagePublicRecord($page)]);
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
        $headerBackgroundColor = (string)$this->request->getParam('headerBackgroundColor', $backgroundColor);
        $backgroundFileIdRaw = $this->request->getParam('backgroundFileId', '0');
        $backgroundFileId = filter_var($backgroundFileIdRaw, FILTER_VALIDATE_INT);
        $tileColumns = (string)$this->request->getParam('tileColumns', 'auto');
        $mobileColumns = (string)$this->request->getParam('mobileColumns', '1');
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($headline) > 120 ||
            mb_strlen($description) > 1000 || !in_array($layout, ['hero', 'split', 'gallery'], true) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/D', $headingColor) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/D', $descriptionColor) ||
            !in_array($designEffect, ['subtle', 'glass', 'glossy'], true) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/D', $backgroundColor) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/D', $headerBackgroundColor) ||
            $backgroundFileId === false || $backgroundFileId < 0 ||
            !in_array($tileColumns, ['auto', '2', '3', '4', '5'], true) ||
            !in_array($mobileColumns, ['auto', '1', '2', '3'], true)) {
            return $this->error('Ungültige Startseiten-Einstellungen');
        }
        $requested = json_decode((string)$this->request->getParam('tourIds', '[]'), true);
        $requestedBindings = json_decode((string)$this->request->getParam('tourShareBindings', '{}'), true);
        if (!is_array($requestedBindings) || array_is_list($requestedBindings) && $requestedBindings !== []) {
            return $this->error('Ungültige Freigabelink-Zuordnung');
        }
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
        $bindings = [];
        foreach ($tourIds as $tourId) {
            $binding = $requestedBindings[$tourId] ?? 'main';
            if (!is_string($binding) || ($binding !== 'main' &&
                !preg_match('/^share:[a-f0-9]{16}$/D', $binding))) {
                return $this->error('Ungültiger Tour-Freigabelink');
            }
            // Keep an explicitly selected but later revoked link pinned and unavailable.
            $bindings[$tourId] = $binding;
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
            $page['headerBackgroundColor'] = $headerBackgroundColor;
            $page['backgroundFileId'] = $backgroundFileId;
            $page['tileColumns'] = $tileColumns;
            $page['mobileColumns'] = $mobileColumns;
            $page['tourIds'] = $tourIds;
            $page['tourShareBindings'] = $bindings;
            $page['modified'] = gmdate('c');
            $this->persistStartPages($uid, $pages);
            return new JSONResponse(['success' => true, 'page' => $this->startPagePublicRecord($page)]);
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
            unset($copy['publicToken'], $copy['sharePasswordHash'], $copy['passwordProtected'], $copy['shareLinks'], $copy['shareExpiresOn']);
            $copy['tourPasswordMode'] = 'keep';
            $copy['tourPasswordSelected'] = [];
            $pages[] = $copy;
            $this->persistStartPages($uid, $pages);
            return new JSONResponse(['success' => true, 'page' => $this->startPagePublicRecord($copy)]);
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
        foreach ($pages as $oldPage) {
            if ($oldPage['id'] === $id) {
                if (!empty($oldPage['publicToken'])) {
                    $this->config->deleteAppValue(self::APP_ID, $this->startPageIndexKey($oldPage['publicToken']));
                }
                foreach ($oldPage['shareLinks'] ?? [] as $link) {
                    if (is_array($link) && !empty($link['token'])) {
                        $this->config->deleteAppValue(self::APP_ID, $this->startPageIndexKey($link['token']));
                    }
                }
            }
        }
        $this->persistStartPages($uid, $remaining);
        return new JSONResponse(['success' => true]);
    }


    /**
     * Owner-controlled public start-page share.
     * Every included tour continues to require its own existing valid public link.
     */
    #[NoAdminRequired]
    public function shareStartPage(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $enabled = (string)$this->request->getParam('enabled', '0') === '1';
        $expiry = $this->request->getParam('expiresOn', '');
        if (!is_string($expiry) || ($expiry !== '' &&
            (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $expiry) ||
             !($date = \DateTimeImmutable::createFromFormat('!Y-m-d', $expiry, new \DateTimeZone('UTC'))) ||
             $date->format('Y-m-d') !== $expiry || $this->shareExpiryReached($expiry, $uid)))) {
            return $this->error('Ungültiges Ablaufdatum');
        }
        $password = $this->request->getParam('password', '');
        $passwordAction = (string)$this->request->getParam('passwordAction', '');
        if (!is_string($password) || strlen($password) > 512 || !in_array($passwordAction, ['', 'remove'], true)) {
            return $this->error('Ungültige Passwort-Aktion');
        }
        if ($enabled && $password !== '' && ($error = $this->validateSharePassword($password)) !== null) {
            return $error;
        }
        $modeRaw = $this->request->getParam('tourPasswordMode', null);
        if ($modeRaw !== null && (!is_string($modeRaw) ||
            !in_array($modeRaw, ['keep', 'all', 'selected'], true))) {
            return $this->error('Ungültige Tour-Passwortregel');
        }
        $selectedRaw = $this->request->getParam('tourPasswordSelected', null);
        $selected = null;
        if ($selectedRaw !== null) {
            $selected = is_string($selectedRaw) ? json_decode($selectedRaw, true) : $selectedRaw;
            if (!is_array($selected) || !array_is_list($selected) || count($selected) > 30 ||
                count($selected) !== count(array_unique(array_filter($selected, 'is_string'))) ||
                count(array_filter($selected, 'is_string')) !== count($selected)) {
                return $this->error('Ungültige Tour-Auswahl für die Passwortregel');
            }
        }
        // PPI PROTECTED TOUR TILE VISIBILITY 2026-11-24
        $showProtectedRaw = $this->request->getParam('showPasswordProtectedTours', null);
        if ($showProtectedRaw !== null && !in_array($showProtectedRaw, ['0', '1', 0, 1, false, true], true)) {
            return $this->error('Ungültige Sichtbarkeitseinstellung');
        }
        $pages = $this->startPageRecords($uid);
        foreach ($pages as &$page) {
            if ($page['id'] !== $id) { continue; }
            $effectiveMode = $modeRaw ?? ($page['tourPasswordMode'] ?? 'keep');
            $effectiveSelected = $selected ?? ($page['tourPasswordSelected'] ?? []);
            if (!is_array($effectiveSelected) || !is_array($page['tourIds'] ?? null)) {
                return $this->error('Ungültige Tour-Konfiguration');
            }
            foreach ($effectiveSelected as $tourId) {
                if (!in_array($tourId, $page['tourIds'], true)) {
                    return $this->error('Nur eingebundene Touren dürfen ausgewählt werden');
                }
            }
            $willHavePassword = $enabled && $passwordAction !== 'remove' &&
                ($password !== '' || !empty($page['sharePasswordHash']));
            if ($enabled && $effectiveMode !== 'keep' && !$willHavePassword) {
                return $this->error('Zum Freischalten von Touren muss die Startseite passwortgeschützt sein');
            }
            $oldToken = $page['publicToken'] ?? null;
            if (!$enabled) {
                unset($page['publicToken']);
                if (is_string($oldToken)) {
                    $this->config->deleteAppValue(self::APP_ID, $this->startPageIndexKey($oldToken));
                }
            } else {
                if (empty($page['publicToken'])) {
                    $page['publicToken'] = bin2hex(random_bytes(24));
                }
                if ($passwordAction === 'remove') { unset($page['sharePasswordHash']); }
                elseif ($password !== '') { $page['sharePasswordHash'] = password_hash($password, PASSWORD_DEFAULT); }
                if ($expiry === '') { unset($page['shareExpiresOn']); }
                else { $page['shareExpiresOn'] = $expiry; }
                $this->config->setAppValue(self::APP_ID, $this->startPageIndexKey($page['publicToken']),
                    json_encode(['uid' => $uid, 'id' => $id]));
            }
            if ($showProtectedRaw !== null) {
                $page['showPasswordProtectedTours'] = (string)$showProtectedRaw === '1';
            }
            if ($enabled) {
                $page['tourPasswordMode'] = $effectiveMode;
                $page['tourPasswordSelected'] = $effectiveMode === 'selected' ? $effectiveSelected : [];
            } elseif (empty($page['shareLinks'])) {
                $page['tourPasswordMode'] = 'keep';
                $page['tourPasswordSelected'] = [];
            } // Keep policies when secondary links remain active.
            $page['modified'] = gmdate('c');
            $this->persistStartPages($uid, $pages);
            return new JSONResponse(['success' => true, 'page' => $this->startPagePublicRecord($page)]);
        }
        unset($page);
        return $this->error('Startseite nicht gefunden', 404);
    }

    /** PPI STARTPAGE MULTISHARE V1 2026-11-24 – rotate main link only. */
    #[NoAdminRequired]
    public function rotateStartPageToken(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $pages = $this->startPageRecords($uid);
        foreach ($pages as &$page) {
            if ($page['id'] !== $id) { continue; }
            if (empty($page['publicToken'])) { return $this->error('Hauptlink ist nicht aktiv', 409); }
            $old = $page['publicToken'];
            $new = bin2hex(random_bytes(24));
            $page['publicToken'] = $new;
            $page['modified'] = gmdate('c');
            // Store new index and record before revoking old index; no recycled tokens.
            $this->config->setAppValue(self::APP_ID, $this->startPageIndexKey($new), json_encode(['uid'=>$uid,'id'=>$id]));
            $this->persistStartPages($uid, $pages);
            $this->config->deleteAppValue(self::APP_ID, $this->startPageIndexKey($old));
            return new JSONResponse(['success'=>true,'page'=>$this->startPagePublicRecord($page)]);
        }
        return $this->error('Startseite nicht gefunden', 404);
    }

    /** Secondary start-page shares are independent of main-link activation. */
    #[NoAdminRequired]
    public function createStartPageShareLink(string $id): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $name = trim((string)$this->request->getParam('name', ''));
        $expiry = $this->validateShareExpiry($this->request->getParam('expiresOn', ''));
        $password = $this->request->getParam('password', '');
        if ($name === '' || mb_strlen($name) > 60 || $expiry === null || !is_string($password)) {
            return $this->error('Ungültige Bezeichnung, Ablaufdatum oder Passwort');
        }
        if ($password !== '' && ($error = $this->validateSharePassword($password)) !== null) { return $error; }
        $pages = $this->startPageRecords($uid);
        foreach ($pages as &$page) {
            if ($page['id'] !== $id) { continue; }
            $links = $page['shareLinks'] ?? [];
            if (!is_array($links) || count($links) >= 12) { return $this->error('Maximal 12 zusätzliche Links pro Startseite'); }
            $token = bin2hex(random_bytes(24));
            $link = ['id'=>bin2hex(random_bytes(8)), 'token'=>$token, 'name'=>$name,
                'expiresOn'=>$expiry, 'created'=>gmdate('c'),
                'tourPasswordMode'=>'keep', 'tourPasswordSelected'=>[],
                'showPasswordProtectedTours'=>true];
            if ($password !== '') { $link['passwordHash'] = password_hash($password, PASSWORD_DEFAULT); }
            $links[] = $link;
            $page['shareLinks'] = $links;
            $page['modified'] = gmdate('c');
            $this->config->setAppValue(self::APP_ID, $this->startPageIndexKey($token), json_encode(['uid'=>$uid,'id'=>$id]));
            $this->persistStartPages($uid, $pages);
            return new JSONResponse(['success'=>true,'page'=>$this->startPagePublicRecord($page)]);
        }
        return $this->error('Startseite nicht gefunden', 404);
    }

    #[NoAdminRequired]
    public function updateStartPageShareLink(string $id, string $linkId): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $action = (string)$this->request->getParam('action', '');
        if (!in_array($action, ['settings','password','removePassword','disable','enable','revoke','rotate','tourPolicy'], true)) {
            return $this->error('Ungültige Aktion');
        }
        $name = null; $expiry = null; $password = null;
        if ($action === 'settings') {
            $name = trim((string)$this->request->getParam('name',''));
            $expiry = $this->validateShareExpiry($this->request->getParam('expiresOn',''));
            if ($name === '' || mb_strlen($name) > 60 || $expiry === null) { return $this->error('Ungültige Link-Einstellungen'); }
        }
        if ($action === 'password') {
            $password = $this->request->getParam('password','');
            if (!is_string($password)) { return $this->error('Ungültiges Passwort'); }
            if (($error = $this->validateSharePassword($password)) !== null) { return $error; }
        }
        $mode = null; $selected = []; $showProtected = true;
        if ($action === 'tourPolicy') {
            $mode = (string)$this->request->getParam('tourPasswordMode', 'keep');
            $selectedRaw = json_decode((string)$this->request->getParam('tourPasswordSelected', '[]'), true);
            $visibility = (string)$this->request->getParam('showPasswordProtectedTours', '1');
            if (!in_array($mode, ['keep','all','selected'], true) || !is_array($selectedRaw) ||
                !array_is_list($selectedRaw) || count($selectedRaw) > 30 ||
                !in_array($visibility, ['0','1'], true)) {
                return $this->error('Ungültige Passwortregel für diesen Startseitenlink');
            }
            $selected = $selectedRaw;
            $showProtected = $visibility === '1';
        }
        $pages = $this->startPageRecords($uid);
        foreach ($pages as &$page) {
            if ($page['id'] !== $id) { continue; }
            foreach ($page['shareLinks'] ?? [] as $i => $link) {
                if (!is_array($link) || ($link['id'] ?? '') !== $linkId) { continue; }
                if ($action === 'enable' && !empty($link['expiresOn']) && $this->shareExpiryReached($link['expiresOn'], $uid)) {
                    return $this->error('Vor dem Aktivieren das Ablaufdatum anpassen');
                }
                if ($action === 'tourPolicy') {
                    if ($mode !== 'keep' && empty($link['passwordHash'])) {
                        return $this->error('Zum Freischalten von Touren benötigt dieser Startseitenlink ein eigenes Passwort');
                    }
                    $allowed = $page['tourIds'] ?? [];
                    $selected = array_values(array_unique($selected));
                    foreach ($selected as $tourId) {
                        if (!is_string($tourId) || !in_array($tourId, $allowed, true)) {
                            return $this->error('Nur eingebundene Touren können freigeschaltet werden');
                        }
                    }
                    if ($mode !== 'selected') { $selected = []; }
                }
                $oldToken = (string)$link['token'];
                if ($action === 'revoke') {
                    array_splice($page['shareLinks'], $i, 1);
                } else {
                    if ($action === 'settings') { $link['name']=$name; $link['expiresOn']=$expiry; }
                    if ($action === 'password') { $link['passwordHash']=password_hash($password, PASSWORD_DEFAULT); }
                    if ($action === 'removePassword') {
                        unset($link['passwordHash']);
                        // Fail closed: never retain a bypass policy on a passwordless link.
                        $link['tourPasswordMode']='keep';
                        $link['tourPasswordSelected']=[];
                    }
                    if ($action === 'tourPolicy') {
                        $link['tourPasswordMode']=$mode;
                        $link['tourPasswordSelected']=$selected;
                        $link['showPasswordProtectedTours']=$showProtected;
                    }
                    if ($action === 'disable') { $link['disabled']=true; }
                    if ($action === 'enable') { unset($link['disabled']); }
                    if ($action === 'rotate') {
                        $link['token']=bin2hex(random_bytes(24));
                        $this->config->setAppValue(self::APP_ID, $this->startPageIndexKey($link['token']), json_encode(['uid'=>$uid,'id'=>$id]));
                    }
                    $page['shareLinks'][$i] = $link;
                }
                $page['modified'] = gmdate('c');
                $this->persistStartPages($uid, $pages);
                if ($action === 'revoke' || $action === 'rotate') {
                    $this->config->deleteAppValue(self::APP_ID, $this->startPageIndexKey($oldToken));
                }
                return new JSONResponse(['success'=>true,'page'=>$this->startPagePublicRecord($page)]);
            }
            return $this->error('Freigabelink nicht gefunden', 404);
        }
        return $this->error('Startseite nicht gefunden', 404);
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicStartPageUnlock(string $token): Response {
        $path = $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicStartPage', ['token' => $token]);
        $found = $this->findPublicStartPage($token);
        if ($found === null || empty($found['page']['sharePasswordHash'])) {
            return new RedirectResponse($path, 303);
        }
        $ip = $this->request->getRemoteAddress();
        $action = 'photosphere_startpage_password';
        $this->throttler->sleepDelayOrThrowOnMax($ip, $action);
        $password = $this->request->getParam('password', '');
        $key = 'ppi_startpage_unlock_' . hash('sha256', $token);
        if (is_string($password) && strlen($password) <= 512 &&
            password_verify($password, $found['page']['sharePasswordHash'])) {
            $this->session->set($key, hash('sha256', $token . '|' . $found['page']['sharePasswordHash']));
            $this->session->remove('ppi_startpage_error_' . hash('sha256', $token));
        } else {
            $this->throttler->registerAttempt($action, $ip);
            $this->session->set('ppi_startpage_error_' . hash('sha256', $token), true);
        }
        return new RedirectResponse($path, 303);
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicStartPageBackground(string $token): Response {
        $found = $this->findPublicStartPage($token);
        if ($found === null || !$this->startPageUnlocked($token, $found['page'])) {
            return $this->error('Bild nicht verfügbar', 404);
        }
        $id = (int)($found['page']['backgroundFileId'] ?? 0);
        if ($id <= 0) { return $this->error('Bild nicht verfügbar', 404); }
        try {
            $home = $this->rootFolder->getUserFolder($found['owner']);
            foreach ($home->getById($id) as $file) {
                if (!$file instanceof File || !$file->isReadable() || $file->getMimetype() !== 'image/jpeg') { continue; }
                if (!is_string($home->getRelativePath($file->getPath()))) { continue; }
                $preview = $this->previewManager->getPreview($file, 1600, 900, true);
                $response = new FileDisplayResponse($preview, 200, ['Content-Type' => $preview->getMimeType()]);
                $response->addHeader('Cache-Control', 'private, no-store');
                $response->addHeader('X-Content-Type-Options', 'nosniff');
                return $response;
            }
        } catch (\Throwable $e) { /* No original-image fallback and no internal paths exposed. */ }
        return $this->error('Bild nicht verfügbar', 404);
    }

    /** Public derivative only for active, explicitly unprotected start-page links. */
    private function socialStartPageImageFile(array $found): ?File {
        $id = (int)($found['page']['backgroundFileId'] ?? 0);
        if ($id <= 0) { return null; }
        try {
            $home = $this->rootFolder->getUserFolder($found['owner']);
            foreach ($home->getById($id) as $node) {
                if ($node instanceof File && $node->isReadable() && $node->getMimetype() === 'image/jpeg' &&
                    is_string($home->getRelativePath($node->getPath()))) { return $node; }
            }
        } catch (\Throwable $e) { /* Do not expose paths. */ }
        return null;
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicStartPageSocialCover(string $token): Response {
        $found = $this->findPublicStartPage($token);
        if ($found === null || !empty($found['page']['sharePasswordHash']) ||
            $this->globalOptions($found['owner'])['socialPreview'] !== 'cover') {
            return $this->error('Vorschau nicht verfügbar', 404);
        }
        $file = $this->socialStartPageImageFile($found);
        if ($file === null) { return $this->error('Vorschau nicht verfügbar', 404); }
        try {
            $preview = $this->previewManager->getPreview($file, 1200, 630, true);
            $response = new FileDisplayResponse($preview, 200, ['Content-Type'=>$preview->getMimeType()]);
            $response->addHeader('Cache-Control', 'public, max-age=300');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
            return $response;
        } catch (\Throwable $e) { return $this->error('Vorschau nicht verfügbar', 404); }
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicStartPage(string $token): Response {
        $found = $this->findPublicStartPage($token);
        if ($found === null) {
            $response = new TemplateResponse(self::APP_ID, 'startpage-public', ['invalid' => true], 'guest');
            $response->setStatus(404);
            $response->addHeader('Cache-Control', 'no-store');
            return $response;
        }
        $page = $found['page'];
        $mode = $this->globalOptions($found['owner'])['socialPreview'];
        if ($mode !== 'off') {
            $protected = !empty($page['sharePasswordHash']);
            $imageUrl = $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->imagePath(self::APP_ID, 'social-tour-neutral.png')
            );
            $title = $protected ? 'Passwortgeschützte 360°-Startseite' : mb_substr(trim((string)(($page['headline'] ?? '') ?: ($page['name'] ?? '360°-Startseite'))), 0, 100);
            $desc = $protected ? 'Diese 360°-Startseite ist mit einem Passwort geschützt.' : mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($page['description'] ?? '')) ?? ''), 0, 250);
            if ($desc === '') { $desc = 'Entdecke diese interaktive 360°-Startseite.'; }
            if (!$protected && $mode === 'cover' && $this->socialStartPageImageFile($found) !== null) {
                $imageUrl = $this->urlGenerator->getAbsoluteURL(
                    $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicStartPageSocialCover', ['token'=>$token])
                );
            }
            $this->socialTags($title, $desc, $imageUrl, $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicStartPage', ['token'=>$token])
            ));
        }
        if (!$this->startPageUnlocked($token, $found['page'])) {
            $errorKey = 'ppi_startpage_error_' . hash('sha256', $token);
            $badPassword = (bool)$this->session->get($errorKey);
            $this->session->remove($errorKey);
            $response = new TemplateResponse(self::APP_ID, 'startpage-public', [
                'passwordRequired' => true,
                'passwordError' => $badPassword,
                'unlockUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicStartPageUnlock', ['token' => $token]),
            ], 'guest');
            $response->addHeader('Cache-Control', 'no-store');
            return $response;
        }
        $page = $found['page'];
        $availableTours = [];
        foreach ($this->records($found['owner']) as $tour) {
            if (!in_array($tour['id'], $page['tourIds'] ?? [], true)) { continue; }
            $tourToken = $this->startPagePreferredTourToken($found['owner'], $tour, $page);
            // No fallback: a revoked, disabled, expired or deleted pinned link hides this tour.
            if ($tourToken !== null) {
                $resolved = $this->publicTour($tourToken);
                if ($resolved === null || $resolved['owner'] !== $found['owner'] ||
                    $resolved['tour']['id'] !== $tour['id']) { continue; }
                $tourData = $resolved['tour'];
                $imageId = (int)($tourData['display']['startImageId'] ?? 0);
                $ids = array_values(array_filter(array_map(static fn($v) => (int)($v['id'] ?? 0), $tourData['images'] ?? [])));
                if (!in_array($imageId, $ids, true)) { $imageId = (int)($ids[0] ?? 0); }
                $locked = !empty($tourData['sharePasswordHash']);
                $mode = $page['tourPasswordMode'] ?? 'keep';
                if ($locked && !empty($page['sharePasswordHash']) &&
                    ($mode === 'all' || ($mode === 'selected' &&
                        in_array($tour['id'], $page['tourPasswordSelected'] ?? [], true)))) {
                    // This grant is bound to this browser session and exact tour token.
                    // Authorization on every subsequent tour/image request revalidates it.
                    $this->session->set('ppi_startpage_tour_grant_' . hash('sha256', $tourToken), $token);
                }
                $pageGranted = $locked && $this->startPageTourGranted($tourToken, $resolved);
                // PPI PROTECTED TOUR TILE VISIBILITY 2026-11-24
                // Filter only tile visibility; individual tour authorization is unchanged.
                if ($locked && !$pageGranted && ($page['showPasswordProtectedTours'] ?? true) === false) {
                    continue;
                }
                $availableTours[$tour['id']] = [
                    'name' => mb_substr((string)$tour['name'], 0, 100),
                    'description' => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($tourData['display']['introText'] ?? '')) ?? ''), 0, 135),
                    'url' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicView', ['token' => $tourToken]),
                    'image' => (!$locked || $pageGranted) && $imageId > 0 && $this->image($found['owner'], $imageId) !== null
                        ? $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicThumbnail',
                            ['token' => $tourToken, 'fileId' => $imageId]) : '',
                    'locked' => $locked && !$pageGranted,
                ];
            }
        }
        $ordered = [];
        foreach ($page['tourIds'] ?? [] as $tourId) {
            if (isset($availableTours[$tourId])) { $ordered[] = $availableTours[$tourId]; }
        }
        $response = new TemplateResponse(self::APP_ID, 'startpage-public', [
            'page' => $page, 'tours' => $ordered,
            'backgroundUrl' => (int)($page['backgroundFileId'] ?? 0) > 0
                ? $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.publicStartPageBackground', ['token' => $token]) : '',
        ], 'guest');
        $response->addHeader('Cache-Control', 'no-store');
        return $response;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        Util::addScript(self::APP_ID, 'tour-sphere');
        Util::addScript(self::APP_ID, 'tour-editor');
        Util::addScript(self::APP_ID, 'global-settings');
        return new TemplateResponse(self::APP_ID, 'tours', [
            'globalSettingsUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.globalSettings'),
            'startPagesUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.listStartPages'),
            'apiUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.listTours'),
            'pickerPreferencesUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.savePickerPreferences'),
            'pickerPanoramaOnly' => ($this->userId() !== null && $this->config->getUserValue($this->userId(), self::APP_ID, self::PICKER_PANORAMA_ONLY, '0') === '1'),
            'filesUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.browse'),
            'pickerTagsUrl' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.listPickerTags'),
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

    /** Available collaborative Nextcloud system tags, filtered for the signed-in user. */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listPickerTags(): JSONResponse {
        $user = $this->userSession->getUser();
        if ($user === null) { return $this->error('Anmeldung erforderlich', 401); }
        try {
            $visible = [];
            foreach ($this->tagManager->getAllTags(true) as $tag) {
                if (!$this->tagManager->canUserSeeTag($tag, $user)) { continue; }
                $visible[] = ['id' => (string)$tag->getId(), 'name' => $tag->getName()];
            }
            usort($visible, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
            return new JSONResponse(['success' => true, 'tags' => $visible]);
        } catch (\Throwable $e) {
            return $this->error('Nextcloud-Tags konnten nicht geladen werden.', 500);
        }
    }

    /** Exact tag-ID matching, limited to readable JPEGs in the current user's folder. */
    private function matchingSelectedTagFileIds(array $listing, string $selectedTagId, int $limit): array {
        if ($limit <= 0) { return []; }
        $ids = [];
        foreach ($listing as $node) {
            if (count($ids) >= $limit) { break; }
            if ($node instanceof File && $node->isReadable() && $node->getMimetype() === 'image/jpeg') {
                $ids[] = (string)$node->getId();
            }
        }
        $matches = [];
        try {
            foreach (array_chunk($ids, 200) as $chunk) {
                foreach ($this->tagMapper->getTagIdsForObjects($chunk, 'files') as $fileId => $tagIds) {
                    if (in_array($selectedTagId, array_map('strval', $tagIds), true)) {
                        $matches[(string)$fileId] = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail closed: do not expose files when tag relations could not be checked.
            return [];
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
            $searchMode = (string)$this->request->getParam('searchMode', 'name');
            if (!in_array($searchMode, ['name', 'tags'], true)) { $searchMode = 'name'; }
            if (mb_strlen($query) > 120) { return $this->error('Suchbegriff ist zu lang'); }
            // Exact tag selection is independent from the optional text search.
            $selectedTagId = $searchMode === 'tags' ? trim((string)$this->request->getParam('tagId', '')) : '';
            if ($selectedTagId !== '' && (!ctype_digit($selectedTagId) || strlen($selectedTagId) > 18 || (int)$selectedTagId <= 0)) {
                return $this->error('Ungültiges Schlagwort');
            }
            if ($selectedTagId !== '') {
                // Never allow a guessed tag id to bypass user/group visibility.
                $user = $this->userSession->getUser();
                try {
                    $tags = $user !== null ? $this->tagManager->getTagsByIds([$selectedTagId], $user) : [];
                    $tag = reset($tags);
                    if ($tag === false || !$this->tagManager->canUserSeeTag($tag, $user)) {
                        return $this->error('Schlagwort nicht verfügbar', 404);
                    }
                } catch (\Throwable $e) { return $this->error('Schlagwort nicht verfügbar', 404); }
            }
            $recursive = (string)$this->request->getParam('recursive', '0') === '1';
            $sort = (string)$this->request->getParam('sort', 'name_asc');
            if (!in_array($sort, ['name_asc', 'name_desc', 'newest', 'oldest', 'size_desc'], true)) {
                $sort = 'name_asc';
            }
            $date = (string)$this->request->getParam('date', 'any');
            $size = (string)$this->request->getParam('size', 'any');
            $panoramaOnly = (string)$this->request->getParam('panoramaOnly', '0') === '1';
            if (!in_array($date, ['any', '30d', 'year'], true)) { $date = 'any'; }
            if (!in_array($size, ['any', 'lt20', 'gt20', 'gt30', 'gt40', 'gt50'], true)) { $size = 'any'; }
            $threshold = $date === '30d' ? time() - 30 * 86400
                : ($date === 'year' ? strtotime(gmdate('Y') . '-01-01 00:00:00 UTC') : 0);
            $mb20 = 20 * 1024 * 1024;
            $mb30 = 30 * 1024 * 1024;
            $mb40 = 40 * 1024 * 1024;
            $mb50 = 50 * 1024 * 1024;
            $folders = [];
            $images = [];
            $pending = [$folder];
            $visited = [];
            $scanned = 0;
            $truncated = false;
            $nonPanoramasExcluded = 0;
            $totalPanoramas = 0;
            $filteredPanoramas = 0;
            // Count recognized panoramas before applying search/date/size conditions.
            // Bound recursive traversal to keep large/external mounts responsive.
            // Only list readable nodes from the signed-in user's accessible tree.
            while ($pending !== [] && $scanned < 3000) {
                $current = array_shift($pending);
                $currentId = $current->getId();
                if (isset($visited[$currentId])) { continue; }
                $visited[$currentId] = true;
                try { $listing = $current->getDirectoryListing(); }
                catch (\Throwable $e) { continue; }
                $tagMatches = $searchMode === 'name' ? []
                    : $this->matchingTaggedFileIds($listing, $query, max(0, 3000 - $scanned), false);
                $selectedTagMatches = $selectedTagId !== ''
                    ? $this->matchingSelectedTagFileIds($listing, $selectedTagId, max(0, 3000 - $scanned)) : [];
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
                    $isPanorama = $this->isPanoramaFile($node);
                    if ($isPanorama) { ++$totalPanoramas; }
                    if ($selectedTagId !== '' && !isset($selectedTagMatches[(string)$node->getId()])) { continue; }
                    if ($query !== '') {
                        $nameMatch = $searchMode === 'name' && mb_stripos($node->getName(), $query) !== false;
                        $tagMatch = $searchMode === 'tags' && isset($tagMatches[(string)$node->getId()]);
                        if (!$nameMatch && !$tagMatch) { continue; }
                    }
                    $mtime = (int)$node->getMtime();
                    $bytes = (int)$node->getSize();
                    if ($threshold > 0 && $mtime < $threshold) { continue; }
                    if ($size === 'lt20' && $bytes >= $mb20) { continue; }
                    if ($size === 'gt20' && $bytes <= $mb20) { continue; }
                    if ($size === 'gt30' && $bytes <= $mb30) { continue; }
                    if ($size === 'gt40' && $bytes <= $mb40) { continue; }
                    if ($size === 'gt50' && $bytes <= $mb50) { continue; }
                    if ($isPanorama) { ++$filteredPanoramas; }
                    if ($panoramaOnly && !$isPanorama) {
                        ++$nonPanoramasExcluded;
                        continue;
                    }
                    $images[] = ['id' => $node->getId(), 'name' => $node->getName(),
                        'path' => '/' . ltrim($relative, '/'), 'type' => 'image',
                        'isPanorama' => $isPanorama,
                        'mtime' => $mtime, 'size' => $bytes];
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
                'totalPanoramas' => $totalPanoramas, 'filteredPanoramas' => $filteredPanoramas,
                'searchMode' => $searchMode, 'tagId' => $selectedTagId]);
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
                if (is_string($expiry) && $expiry !== '' && $this->shareExpiryReached($expiry, $entry['uid'])) { return null; }
                return ['owner' => $entry['uid'], 'tour' => $tour];
            }
            // Per-link credentials must never inherit the legacy link's password or expiry.
            foreach ($tour['shareLinks'] ?? [] as $link) {
                if (!is_array($link) || !isset($link['token']) || !is_string($link['token'])
                    || !hash_equals($link['token'], $token) || !empty($link['disabled'])) { continue; }
                $expiry = $link['expiresOn'] ?? null;
                if (is_string($expiry) && $expiry !== '' && $this->shareExpiryReached($expiry, $entry['uid'])) { return null; }
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
                $date->format('Y-m-d') !== $expiryRequest || $this->shareExpiryReached($expiryRequest, $uid)))) {
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

    /** Expiry means 00:00 at the start of the chosen date in the owner's timezone.
     * Core user preference and Nextcloud default_timezone are used; never visitor timezone.
     */
    private function shareExpiryZone(string $owner): \DateTimeZone {
        $personal = $owner !== '' ? $this->config->getUserValue($owner, 'core', 'timezone', '') : '';
        $default = $this->config->getSystemValueString('default_timezone', 'UTC');
        foreach ([$personal, $default, 'UTC'] as $zoneName) {
            if (!is_string($zoneName) || $zoneName === '') { continue; }
            try { return new \DateTimeZone($zoneName); }
            catch (\Throwable $e) { /* Nextcloud fallback */ }
        }
        return new \DateTimeZone('UTC');
    }

    private function shareExpiryReached(string $expiry, string $owner): bool {
        if ($expiry === '') { return false; }
        $zone = $this->shareExpiryZone($owner);
        $midnight = \DateTimeImmutable::createFromFormat('!Y-m-d', $expiry, $zone);
        // Invalid stored expiry must never grant public access.
        if ($midnight === false || $midnight->format('Y-m-d') !== $expiry) { return true; }
        return new \DateTimeImmutable('now', $zone) >= $midnight;
    }

    private function validateShareExpiry(mixed $raw): ?string {
        if (!is_string($raw) || $raw === '') { return $raw === '' ? '' : null; }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw, new \DateTimeZone('UTC'));
        return ($date && $date->format('Y-m-d') === $raw && !$this->shareExpiryReached($raw, $this->userId() ?? '')) ? $raw : null;
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
        if (!in_array($action, ['settings', 'password', 'removePassword', 'disable', 'enable', 'revoke', 'rotate'], true)) {
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
                        if (!empty($link['expiresOn']) && $this->shareExpiryReached($link['expiresOn'], $uid)) {
                            return $this->error('Vor dem Aktivieren bitte das Ablaufdatum aktualisieren');
                        }
                        foreach ($tour['images'] ?? [] as $item) {
                            if (empty($item['id']) || $this->image($uid, (int)$item['id']) === null) {
                                return $this->error('Mindestens ein Panorama ist nicht mehr zugänglich', 403);
                            }
                        }
                        unset($link['disabled']);
                    }
                    if ($action === 'rotate') {
                        $oldToken = (string)($link['token'] ?? '');
                        if (!preg_match('/^[a-f0-9]{48}$/D', $oldToken)) {
                            return $this->error('Ungültiges bisheriges Freigabe-Token', 409);
                        }

                        // Eigenständiges, kryptografisch sicheres Token.
                        // Niemals einen bestehenden Freigabeindex überschreiben.
                        do {
                            $newToken = bin2hex(random_bytes(24));
                        } while ($this->config->getAppValue(
                            self::APP_ID,
                            $this->shareIndexKey($newToken),
                            ''
                        ) !== '');

                        // Zuerst neuen Index vorbereiten.
                        $this->config->setAppValue(
                            self::APP_ID,
                            $this->shareIndexKey($newToken),
                            json_encode(['uid' => $uid, 'id' => $id])
                        );

                        // Nur den Token dieses Zusatzlinks ersetzen.
                        // Passwort, Name, Ablaufdatum und Status bleiben erhalten.
                        $link['token'] = $newToken;
                    }

                    $tour['modified'] = gmdate('c');
                    $this->persist($uid, $tours);

                    if ($action === 'rotate') {
                        // Alter Link und alte Besuchersitzungen werden ungültig.
                        $this->config->deleteAppValue(
                            self::APP_ID,
                            $this->shareIndexKey($oldToken)
                        );
                    }
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

    /** Shared settings for all tours and start pages of one owner, never system-wide. */
    private function globalOptions(string $uid): array {
        $raw = json_decode($this->config->getUserValue($uid, self::APP_ID, 'global_ui_options', '{}'), true);
        $raw = is_array($raw) ? $raw : [];
        return [
            'socialPreview' => in_array($raw['socialPreview'] ?? null, ['cover','neutral','off'], true) ? $raw['socialPreview'] : 'cover',
            'tileLayout' => in_array($raw['tileLayout'] ?? null, ['adaptive','compact'], true) ? $raw['tileLayout'] : 'adaptive',
            'tileSort' => in_array($raw['tileSort'] ?? null, ['original','name_asc','name_desc','newest','oldest'], true) ? $raw['tileSort'] : 'original',
        ];
    }

    #[NoAdminRequired]
    public function globalSettings(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        return new JSONResponse(['success' => true, 'settings' => $this->globalOptions($uid)]);
    }

    #[NoAdminRequired]
    public function saveGlobalSettings(): JSONResponse {
        $uid = $this->userId();
        if ($uid === null) { return $this->error('Anmeldung erforderlich', 401); }
        $social = $this->request->getParam('socialPreview', null);
        $layout = $this->request->getParam('tileLayout', null);
        $sort = $this->request->getParam('tileSort', null);
        if (!is_string($social) || !in_array($social, ['cover','neutral','off'], true) ||
            !is_string($layout) || !in_array($layout, ['adaptive','compact'], true) ||
            !is_string($sort) || !in_array($sort, ['original','name_asc','name_desc','newest','oldest'], true)) {
            return $this->error('Ungültige globale Einstellungen');
        }
        $options = ['socialPreview'=>$social, 'tileLayout'=>$layout, 'tileSort'=>$sort];
        $this->config->setUserValue($uid, self::APP_ID, 'global_ui_options', json_encode($options));
        return new JSONResponse(['success'=>true, 'settings'=>$options]);
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
        $mode = $this->globalOptions($found['owner'])['socialPreview'];
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
        $mode = $this->globalOptions($found['owner'])['socialPreview'];
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
        if (!empty($found['tour']['sharePasswordHash']) && !$this->unlocked($token, $found['tour']) &&
            !$this->startPageTourGranted($token, $found)) {
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
                $watermarkEnabled = filter_var($input['watermarkEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $watermarkId = (string)($input['watermarkId'] ?? 'none');
                if ($watermarkEnabled && !$this->tourWatermarkAllowed($watermarkId)) {
                    return $this->error('Wasserzeichen nicht für Touren freigegeben', 403);
                }
                if ($startId === false || ($startId !== 0 && !isset($verified[(string)$startId])) ||
                    $startYaw === false || $startYaw < -180 || $startYaw > 180 ||
                    !in_array($size, ['xsmall','small','medium','large','xlarge'], true) ||
                    !in_array($style, ['classic','blue','map-pin','label-pin','outline-pin','gps-pin','you-pin'], true) ||
                    !preg_match('/^#[0-9a-fA-F]{6}$/D', $svgPinColor)) {
                    return $this->error('Ungültige Startansicht oder Hotspot-Größe');
                }
                $tour['display'] = [
                    'startImageId' => $startId, 'startYaw' => $startYaw,
                    'introText' => $description,
                    'hotspotSize' => $size,
                    'hotspotStyle' => $style,
                    'svgPinColor' => strtolower($svgPinColor),
                    // Legacy per-tour socialPreviewMode is superseded by global owner settings.
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
