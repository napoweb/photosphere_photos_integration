<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\Constants;
use OCP\AppFramework\PublicShareController;
use OCP\Share\IShare;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\Share\IManager;

/** Watermark administration. Public reads expose ONLY the active image and rendering settings. */
final class WatermarkController extends Controller {
    private const APP_ID = 'photosphere_photos_integration';
    private const MAX_BYTES = 2097152;
    private const MAX_UPLOADS = 5;
    private const LEGACY_FILE = 'watermark.png';
    private const PNG_SIGNATURE = "\x89PNG\r\n\x1a\n";

    public function __construct(
        IRequest $request,
        private IAppData $appData,
        private IConfig $config,
        private IRootFolder $rootFolder,
        private IUserSession $userSession,
        private IUserManager $userManager,
        private IManager $shareManager,
        private ISession $session,
    ) {
        parent::__construct(self::APP_ID, $request);
    }

    private function customFolder() {
        try { return $this->appData->getFolder('watermark'); }
        catch (\OCP\Files\NotFoundException $e) { return $this->appData->newFolder('watermark'); }
    }

    /** @return array<string, string> */
    private function library(): array {
        $raw = json_decode($this->config->getAppValue(self::APP_ID, 'watermark_library', '{}'), true);
        if (!is_array($raw)) { $raw = []; }
        $items = [];
        foreach ($raw as $id => $label) {
            if (is_string($id) && preg_match('/^wm-[a-f0-9]{16}$/D', $id) && is_string($label)) {
                $items[$id] = mb_substr($label, 0, 100);
            }
        }
        // Preserve the existing 0.4.6 uploaded image during upgrades, without copying it.
        try {
            if ($this->appData->getFolder('watermark')->fileExists(self::LEGACY_FILE)) {
                $items = ['legacy' => 'Bisheriges Wasserzeichen (0.4.6)'] + $items;
            }
        } catch (\OCP\Files\NotFoundException $e) {}
        return $items;
    }

    private function storeLibrary(array $items): void {
        unset($items['legacy']);
        $this->config->setAppValue(self::APP_ID, 'watermark_library', json_encode($items, JSON_UNESCAPED_UNICODE));
    }

    private function fileName(string $id): ?string {
        if ($id === 'legacy') { return self::LEGACY_FILE; }
        if (preg_match('/^wm-[a-f0-9]{16}$/D', $id)) { return $id . '.png'; }
        return null;
    }

    private function activeId(): string {
        $configured = $this->config->getAppValue(self::APP_ID, 'watermark_active', '');
        $items = $this->library();
        if ($configured === 'none') { return 'none'; }
        if ($configured === 'default') { return $this->defaultHidden() ? 'none' : 'default'; }
        if ($configured !== '' && array_key_exists($configured, $items)) { return $configured; }
        // No active watermark is the safe default; saved graphics stay available.
        return 'none';
    }

    private function defaultHidden(): bool {
        return $this->config->getAppValue(self::APP_ID, 'watermark_hide_default', 'no') === 'yes';
    }

    private function revision(): string {
        return $this->config->getAppValue(self::APP_ID, 'watermark_revision', 'default');
    }

    private function bumpRevision(): string {
        $revision = bin2hex(random_bytes(8));
        $this->config->setAppValue(self::APP_ID, 'watermark_revision', $revision);
        return $revision;
    }

    /** @return array<string, mixed> */
    private function options(): array {
        $defaults = [
            'enabled' => true,
            'mode' => 'panorama',
            'yaw' => 0,
            'pitchDesktop' => 40,
            'pitchMobile' => 56,
            'screenX' => 88,
            'screenY' => 85,
            'sizeDesktop' => 196,
            'sizeMobile' => 108,
        ];
        $loaded = json_decode($this->config->getAppValue(self::APP_ID, 'watermark_options', '{}'), true);
        if (!is_array($loaded)) { $loaded = []; }
        return array_merge($defaults, array_intersect_key($loaded, $defaults));
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function settings(): JSONResponse {
        $response = new JSONResponse([
            'success' => true,
            'options' => $this->options(),
            'active' => $this->activeId(),
            'revision' => $this->revision(),
        ]);
        $response->addHeader('Cache-Control', 'no-store, max-age=0');
        return $response;
    }

    /** Only currently selected watermark is available to anonymous viewers. */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function get(): DataDisplayResponse {
        if ($this->activeId() === 'none') {
            return new DataDisplayResponse('', 404);
        }
        return $this->imageResponse($this->activeId());
    }

    // Admin-only GET preview. NoCSRFRequired does not remove admin authorization.
    #[NoCSRFRequired]
    public function preview(): DataDisplayResponse {
        $id = (string)$this->request->getParam('id', 'default');
        if ($id !== 'none' && !($id === 'default' && !$this->defaultHidden()) && !array_key_exists($id, $this->library())) {
            return new DataDisplayResponse('', 404);
        }
        return $this->imageResponse($id);
    }

    private function imageResponse(string $id): DataDisplayResponse {
        $body = null;
        $fileName = $this->fileName($id);
        if ($fileName !== null) {
            try {
                $folder = $this->appData->getFolder('watermark');
                if ($folder->fileExists($fileName)) { $body = $folder->getFile($fileName)->getContent(); }
            } catch (\OCP\Files\NotFoundException $e) {}
        }
        if ($body === null && $id === 'default') { $body = file_get_contents(__DIR__ . '/../../img/watermark-napohd.png'); }
        if ($body === null) { return new DataDisplayResponse('', 404); }
        return new DataDisplayResponse((string)$body, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="watermark.png"',
            'Cache-Control' => 'no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // Admin-only GET gallery. Writing operations keep their CSRF checks.
    #[NoCSRFRequired]
    public function listImages(): JSONResponse {
        $items = [['id' => 'none', 'label' => 'Kein Wasserzeichen']];
        if (!$this->defaultHidden()) {
            $items[] = ['id' => 'default', 'label' => '360°-Standardgrafik'];
        }
        foreach ($this->library() as $id => $label) {
            $items[] = ['id' => $id, 'label' => $label];
        }
        return new JSONResponse([
            'success' => true,
            'images' => $items,
            'active' => $this->activeId(),
            'options' => $this->options(),
            'revision' => $this->revision(),
            'count' => count($this->library()),
            'maxUploads' => self::MAX_UPLOADS,
            'defaultHidden' => $this->defaultHidden(),
            'folderRules' => $this->visibleFolderRules(),
        ]);
    }

    /** @param array<string, mixed> $options */
    private function validateOptions(array $options): ?array {
        $allowed = [
            'yaw' => [-180, 180],
            'pitchDesktop' => [-80, 80],
            'pitchMobile' => [-80, 80],
            'screenX' => [0, 100],
            'screenY' => [0, 100],
            'sizeDesktop' => [40, 500],
            'sizeMobile' => [30, 300],
        ];
        $validated = [];
        if (!isset($options['enabled']) || !is_bool($options['enabled'])) { return null; }
        if (!in_array($options['mode'] ?? null, ['panorama', 'screen'], true)) { return null; }
        $validated['enabled'] = $options['enabled'];
        $validated['mode'] = $options['mode'];
        foreach ($allowed as $key => [$min, $max]) {
            $value = $options[$key] ?? null;
            if (!is_numeric($value) || !is_finite((float)$value)) { return null; }
            $number = (float)$value;
            if ($number < $min || $number > $max) { return null; }
            $validated[$key] = $number;
        }
        return $validated;
    }

    public function saveOptions(): JSONResponse {
        $options = $this->request->getParam('options', null);
        if (is_string($options)) { $options = json_decode($options, true); }
        if (!is_array($options)) {
            return new JSONResponse(['message' => 'Ungültige Einstellungen.'], 400);
        }
        $validated = $this->validateOptions($options);
        if ($validated === null) {
            return new JSONResponse(['message' => 'Einstellungen außerhalb der erlaubten Werte.'], 400);
        }
        $this->config->setAppValue(self::APP_ID, 'watermark_options', json_encode($validated));
        return new JSONResponse(['success' => true, 'options' => $validated, 'revision' => $this->bumpRevision()]);
    }

    public function select(): JSONResponse {
        $id = (string)$this->request->getParam('id', '');
        if ($id !== 'none' && !($id === 'default' && !$this->defaultHidden())
            && !array_key_exists($id, $this->library())) {
            return new JSONResponse(['message' => 'Unbekanntes Wasserzeichen.'], 404);
        }
        $this->config->setAppValue(self::APP_ID, 'watermark_active', $id);
        $revision = $this->bumpRevision();
        return new JSONResponse(['success' => true, 'active' => $this->activeId(), 'revision' => $revision]);
    }

    public function upload(): JSONResponse {
        $images = $this->library();
        if (count($images) >= self::MAX_UPLOADS) {
            return new JSONResponse(['message' => 'Maximal fünf eigene Wasserzeichen. Bitte zuerst eines löschen.'], 409);
        }
        $file = $this->request->getUploadedFile('watermark');
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new JSONResponse(['message' => 'Bitte eine PNG-Datei auswählen.'], 400);
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        if (!$tmp || !is_uploaded_file($tmp) || $size < 1 || $size > self::MAX_BYTES) {
            return new JSONResponse(['message' => 'PNG ungültig oder größer als 2 MB.'], 400);
        }
        $contents = file_get_contents($tmp);
        if (!is_string($contents) || strlen($contents) !== $size || substr($contents, 0, 8) !== self::PNG_SIGNATURE) {
            return new JSONResponse(['message' => 'Die Datei muss eine echte PNG-Grafik sein.'], 400);
        }
        $dimensions = @getimagesizefromstring($contents);
        if (!$dimensions || ($dimensions[2] ?? null) !== IMAGETYPE_PNG ||
            $dimensions[0] > 4096 || $dimensions[1] > 4096 ||
            $dimensions[0] < 1 || $dimensions[1] < 1) {
            return new JSONResponse(['message' => 'Ungültiges PNG oder Abmessungen über 4096 Pixel.'], 400);
        }
        $id = 'wm-' . bin2hex(random_bytes(8));
        $this->customFolder()->newFile($id . '.png', $contents);
        $name = basename((string)($file['name'] ?? 'Wasserzeichen'));
        $images[$id] = mb_substr($name, 0, 100);
        $this->storeLibrary($images);
        $this->config->setAppValue(self::APP_ID, 'watermark_active', $id);
        return new JSONResponse(['success' => true, 'active' => $id, 'revision' => $this->bumpRevision()]);
    }

    public function delete(): JSONResponse {
        $id = (string)$this->request->getParam('id', '');
        $images = $this->library();
        if ($id === 'default') {
            // The packaged asset must stay intact for updates; remove its gallery entry.
            $this->config->setAppValue(self::APP_ID, 'watermark_hide_default', 'yes');
            $this->removeRulesForImage('default');
            if ($this->config->getAppValue(self::APP_ID, 'watermark_active', '') === 'default') {
                $this->config->setAppValue(self::APP_ID, 'watermark_active', 'none');
            }
            return new JSONResponse(['success' => true, 'revision' => $this->bumpRevision()]);
        }
        if ($id === 'none' || !array_key_exists($id, $images)) {
            return new JSONResponse(['message' => 'Unbekanntes Wasserzeichen.'], 404);
        }
        $fileName = $this->fileName($id);
        if ($fileName !== null) {
            try {
                $folder = $this->appData->getFolder('watermark');
                if ($folder->fileExists($fileName)) { $folder->getFile($fileName)->delete(); }
            } catch (\OCP\Files\NotFoundException $e) {}
        }
        unset($images[$id]);
        $this->storeLibrary($images);
        $this->removeRulesForImage($id);
        if ($this->activeId() === $id || $this->config->getAppValue(self::APP_ID, 'watermark_active', '') === $id) {
            $this->config->setAppValue(self::APP_ID, 'watermark_active', 'none');
        }
        return new JSONResponse(['success' => true, 'revision' => $this->bumpRevision()]);
    }

    /** Re-enable the shipped standard PNG as a selectable gallery item. */
    public function restoreDefault(): JSONResponse {
        $this->config->setAppValue(self::APP_ID, 'watermark_hide_default', 'no');
        return new JSONResponse(['success' => true, 'revision' => $this->bumpRevision()]);
    }

    /** Clear active watermark selection without deleting any saved PNG. */
    public function reset(): JSONResponse {
        $this->config->setAppValue(self::APP_ID, 'watermark_active', 'none');
        return new JSONResponse(['success' => true, 'revision' => $this->bumpRevision()]);
    }

    /** Folder IDs are stable when a folder is renamed or moved within storage. */
    private function folderRules(): array {
        $raw = json_decode($this->config->getAppValue(self::APP_ID, 'watermark_folder_rules', '[]'), true);
        if (!is_array($raw)) { return []; }
        $result = [];
        foreach ($raw as $rule) {
            if (!is_array($rule) || !isset($rule['folderId'], $rule['imageId'], $rule['owner'], $rule['path'])) { continue; }
            $id = filter_var($rule['folderId'], FILTER_VALIDATE_INT);
            if (!$id || $id < 1 || !is_string($rule['imageId']) || !is_string($rule['owner']) || !is_string($rule['path'])) { continue; }
            $result[] = [
                'folderId' => (int)$id,
                'imageId' => $rule['imageId'],
                'owner' => $rule['owner'],
                'path' => $rule['path'],
            ];
        }
        return array_slice($result, 0, 100);
    }

    private function storeFolderRules(array $rules): void {
        $this->config->setAppValue(self::APP_ID, 'watermark_folder_rules', json_encode(array_values($rules), JSON_UNESCAPED_UNICODE));
    }

    private function removeRulesForImage(string $imageId): void {
        $this->storeFolderRules(array_filter($this->folderRules(), static fn(array $rule): bool => $rule['imageId'] !== $imageId));
    }

    /**
     * All administration is scoped to the current account's own Files view.
     * That view includes legitimate Nextcloud shares mounted for this user.
     * NEVER call getUserFolder() with a client-selected UID here.
     */
    private function currentFilesRoot(): ?Folder {
        $current = $this->userSession->getUser();
        if ($current === null) { return null; }
        return $this->rootFolder->getUserFolder($current->getUID());
    }

    /** Resolve only a folder found inside the logged-in user's mounted tree. */
    private function accessibleFolderById(int $id): ?Folder {
        if ($id < 1) { return null; }
        $root = $this->currentFilesRoot();
        if ($root === null) { return null; }
        try {
            foreach ($root->getById($id) as $candidate) {
                if (!$candidate instanceof Folder) { continue; }
                // This second lookup verifies the candidate is within the user's Files view.
                $relative = $root->getRelativePath($candidate->getPath());
                if (!is_string($relative)) { continue; }
                if ($relative === '' || $relative === '/') { return $root; }
                $again = $root->get(ltrim($relative, '/'));
                if ($again instanceof Folder && (int)$again->getId() === $id) { return $again; }
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /** Do not leak previously configured foreign-owner paths in the admin gallery. */
    private function visibleFolderRules(): array {
        $visible = [];
        foreach ($this->folderRules() as $rule) {
            if ($this->accessibleFolderById((int)$rule['folderId']) === null) { continue; }
            $visible[] = $rule;
        }
        return $visible;
    }

    /** Admin-only: return only the actual logged-in account; no user directory search. */
    #[NoCSRFRequired]
    public function folderOwners(): JSONResponse {
        $current = $this->userSession->getUser();
        if ($current === null) {
            return new JSONResponse(['message' => 'Anmeldung erforderlich.'], 403);
        }
        return new JSONResponse(['success' => true,
            'users' => [['uid' => $current->getUID(), 'label' => $current->getDisplayName()]],
            'currentUser' => $current->getUID()]);
    }

    /** Admin-only folder picker, restricted to the logged-in user's actual Files view. */
    #[NoCSRFRequired]
    public function browseFolders(): JSONResponse {
        $current = $this->userSession->getUser();
        $owner = trim((string)$this->request->getParam('owner', ''));
        $folderId = filter_var($this->request->getParam('folderId', '0'), FILTER_VALIDATE_INT);
        if ($current === null) { return new JSONResponse(['message' => 'Anmeldung erforderlich.'], 403); }
        if ($owner !== '' && $owner !== $current->getUID()) {
            return new JSONResponse(['message' => 'Kein Zugriff auf fremde Benutzerordner.'], 403);
        }
        if ($folderId === false || $folderId < 0) {
            return new JSONResponse(['message' => 'Ungültige Ordner-ID.'], 400);
        }
        try {
            $root = $this->currentFilesRoot();
            if ($root === null) { return new JSONResponse(['message' => 'Anmeldung erforderlich.'], 403); }
            $folder = $folderId === 0 ? $root : $this->accessibleFolderById((int)$folderId);
            if (!$folder instanceof Folder) {
                return new JSONResponse(['message' => 'Ordner nicht verfügbar oder nicht freigegeben.'], 404);
            }
            $children = [];
            foreach ($folder->getDirectoryListing() as $child) {
                if (!$child instanceof Folder) { continue; }
                // Only show descendants exposed by this user's Nextcloud Files tree.
                if ($this->accessibleFolderById((int)$child->getId()) === null) { continue; }
                $children[] = ['id' => (int)$child->getId(), 'name' => $child->getName()];
                if (count($children) >= 500) { break; }
            }
            usort($children, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
            $path = '/' . trim((string)$root->getRelativePath($folder->getPath()), '/');
            return new JSONResponse(['success' => true, 'owner' => $current->getUID(),
                'folderId' => (int)$folder->getId(),
                'path' => $path === '/' ? '/' : rtrim($path, '/'),
                'isRoot' => $folder->getId() === $root->getId(), 'folders' => $children]);
        } catch (\Throwable $e) {
            return new JSONResponse(['message' => 'Ordner nicht verfügbar oder nicht freigegeben.'], 404);
        }
    }

    /** Admin-only: authorize the folder itself, not the submitted owner/path. */
    public function assignFolder(): JSONResponse {
        $imageId = (string)$this->request->getParam('imageId', '');
        $owner = trim((string)$this->request->getParam('owner', ''));
        $path = trim((string)$this->request->getParam('path', ''));
        $current = $this->userSession->getUser();
        if ($current === null) { return new JSONResponse(['message' => 'Anmeldung erforderlich.'], 403); }
        if ($owner !== $current->getUID()) {
            return new JSONResponse(['message' => 'Zuweisungen zu fremden Benutzerordnern sind nicht erlaubt.'], 403);
        }
        if ($imageId === 'none' || !($imageId === 'default' && !$this->defaultHidden()) && !isset($this->library()[$imageId])) {
            return new JSONResponse(['message' => 'Bitte ein vorhandenes Wasserzeichen wählen.'], 400);
        }
        if ($path === '' || strlen($path) > 1024 || str_contains($path, "\0")) {
            return new JSONResponse(['message' => 'Ungültiger Ordnerpfad.'], 400);
        }
        $path = trim($path, '/');
        if ($path === '' || in_array('..', explode('/', $path), true) || in_array('.', explode('/', $path), true)) {
            return new JSONResponse(['message' => 'Bitte einen Unterordner aus Dateien angeben.'], 400);
        }
        try {
            $root = $this->currentFilesRoot();
            if ($root === null) { return new JSONResponse(['message' => 'Anmeldung erforderlich.'], 403); }
            $candidate = $root->get($path);
            if (!$candidate instanceof Folder || $candidate->getId() === $root->getId()) {
                return new JSONResponse(['message' => 'Ordner nicht gefunden.'], 404);
            }
            $folder = $this->accessibleFolderById((int)$candidate->getId());
            if ($folder === null) {
                return new JSONResponse(['message' => 'Keine Berechtigung für diesen Ordner.'], 403);
            }
            $id = (int)$folder->getId();
            $rules = array_values(array_filter($this->folderRules(), static fn(array $r): bool => $r['folderId'] !== $id));
            if (count($rules) >= 50) {
                return new JSONResponse(['message' => 'Maximal 50 Ordnerregeln möglich.'], 409);
            }
            $relative = trim((string)$root->getRelativePath($folder->getPath()), '/');
            $rules[] = ['folderId' => $id, 'imageId' => $imageId,
                'owner' => $current->getUID(), 'path' => '/' . $relative];
            $this->storeFolderRules($rules);
            return new JSONResponse(['success' => true, 'folderRules' => $this->visibleFolderRules(),
                'revision' => $this->bumpRevision()]);
        } catch (\Throwable $e) {
            return new JSONResponse(['message' => 'Ordner nicht verfügbar oder nicht freigegeben.'], 404);
        }
    }

    /** Admin-only: changing a saved rule also requires current folder access. */
    public function unassignFolder(): JSONResponse {
        $id = filter_var($this->request->getParam('folderId', null), FILTER_VALIDATE_INT);
        if (!$id || $id < 1) { return new JSONResponse(['message' => 'Ungültige Ordner-ID.'], 400); }
        if ($this->accessibleFolderById((int)$id) === null) {
            return new JSONResponse(['message' => 'Keine Berechtigung für diesen Ordner.'], 403);
        }
        $rules = array_values(array_filter($this->folderRules(), static fn(array $r): bool => $r['folderId'] !== (int)$id));
        $this->storeFolderRules($rules);
        return new JSONResponse(['success' => true, 'folderRules' => $this->visibleFolderRules(),
            'revision' => $this->bumpRevision()]);
    }

    /** Validate actual access BEFORE evaluating a private folder rule.
     * For public files, the token must refer to an accessible Nextcloud share.
     * Unsupported public photo-album tokens safely use the default watermark.
     */
    private function accessibleNode(int $fileId, string $shareToken): ?Node {
        if ($fileId < 1) { return null; }
        if ($shareToken !== '') {
            try {
                $share = $this->shareManager->getShareByToken($shareToken);
                // Match 0.4.18: public read-only share and authenticated password session.
                if ($share->getShareType() !== IShare::TYPE_LINK) { return null; }
                if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) { return null; }
                if (!$share->canSeeContent()) { return null; }
                if ($share->isPasswordProtected()) {
                    $hash = $share->getPassword();
                    if (!is_string($hash) || $hash === '') { return null; }
                    $saved = json_decode((string)($this->session->get(
                        PublicShareController::DAV_AUTHENTICATED_FRONTEND
                    ) ?? '[]'), true);
                    if (!is_array($saved) || !is_string($saved[$shareToken] ?? null) ||
                        !hash_equals($hash, $saved[$shareToken])) {
                        return null;
                    }
                }
                $sharedNode = $share->getNode();
                if (!$sharedNode instanceof Node) { return null; }
                if ((int)$sharedNode->getId() === $fileId) { return $sharedNode; }
                if ($sharedNode instanceof Folder) {
                    foreach ($sharedNode->getById($fileId) as $candidate) {
                        if ($candidate instanceof Node) { return $candidate; }
                    }
                }
            } catch (\Throwable $e) {}
            return null;
        }
        $user = $this->userSession->getUser();
        if ($user === null) { return null; }
        try {
            $nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
            foreach ($nodes as $node) { if ($node instanceof Node) { return $node; } }
        } catch (\Throwable $e) {}
        return null;
    }

    private function watermarkForFile(int $fileId, string $shareToken): ?string {
        $default = $this->activeId();
        if (!$this->options()['enabled']) { return 'none'; }
        $node = $this->accessibleNode($fileId, $shareToken);
        if ($node === null) { return null; }
        $rules = [];
        foreach ($this->folderRules() as $rule) { $rules[$rule['folderId']] = $rule['imageId']; }
        $valid = $this->library();
        // Nearest ancestor wins. Parent traversal protects against renamed folders.
        for ($distance = 0; $node !== null && $distance < 100; $distance++) {
            $id = (int)$node->getId();
            if (isset($rules[$id])) {
                $image = $rules[$id];
                if (($image === 'default' && !$this->defaultHidden()) || isset($valid[$image])) { return $image; }
            }
            try {
                $parent = $node->getParent();
                if ($parent->getId() === $id || !$parent instanceof Node) { break; }
                $node = $parent;
            } catch (\Throwable $e) { break; }
        }
        return $default;
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function resolve(): JSONResponse {
        $id = filter_var($this->request->getParam('fileId', null), FILTER_VALIDATE_INT);
        $token = (string)$this->request->getParam('shareToken', '');
        if (strlen($token) > 256) { return new JSONResponse(['message' => 'Ungültiger Token.'], 400); }
        $active = $id ? $this->watermarkForFile((int)$id, $token) : $this->activeId();
        if ($active === null) { return new JSONResponse(['message' => 'Datei nicht verfügbar.'], 404); }
        $response = new JSONResponse(['success' => true, 'active' => $active]);
        $response->addHeader('Cache-Control', 'no-store, max-age=0');
        return $response;
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function contextImage(): DataDisplayResponse {
        $id = filter_var($this->request->getParam('fileId', null), FILTER_VALIDATE_INT);
        $token = (string)$this->request->getParam('shareToken', '');
        if (!$id || strlen($token) > 256) { return new DataDisplayResponse('', 404); }
        $active = $this->watermarkForFile((int)$id, $token);
        if ($active === null || $active === 'none') { return new DataDisplayResponse('', 404); }
        return $this->imageResponse($active);
    }

}
