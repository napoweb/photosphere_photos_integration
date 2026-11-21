<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Controller;

use OCA\PhotospherePhotosIntegration\Service\PanoramaCache;
use OCA\PhotospherePhotosIntegration\Service\UnusedPanoramaCleanup;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

/** Admin-only cache processing switch. Existing cached images remain usable. */
final class PanoramaCacheController extends Controller {
    public function __construct(IRequest $request, private IConfig $config, private UnusedPanoramaCleanup $cleanup, private IUserSession $userSession) {
        parent::__construct(PanoramaCache::APP_ID, $request);
    }

    /** No NoAdminRequired annotation: Nextcloud restricts this to admins. */
    // Read-only endpoint: Nextcloud admin authentication remains mandatory.
    // The CSRF requirement is retained for the save() POST endpoint.
    #[NoCSRFRequired]
    public function status(): JSONResponse {
        return new JSONResponse(['enabled' => $this->enabled(), 'workers' => $this->workers()]);
    }

    /** Read-only, admin-authenticated; POST save retains its CSRF check. */
    #[NoCSRFRequired]
    public function statistics(): JSONResponse {
        try {
            $uid = $this->userSession->getUser()?->getUID();
            if ($uid === null || $uid === '') {
                return new JSONResponse(['message' => 'Anmeldung erforderlich'], 401);
            }
            return new JSONResponse($this->cleanup->statistics($uid));
        } catch (\Throwable $e) {
            // Never infer unused images from a failed/incomplete cross-user scan.
            return new JSONResponse(['message' => 'Cache-Statistik vorübergehend nicht verfügbar. Tourreferenzen konnten nicht vollständig geprüft werden.'], 503);
        }
    }

    public function save(): JSONResponse {
        $enabled = $this->request->getParam('enabled', null);
        if (!in_array($enabled, ['yes', 'no'], true)) {
            return new JSONResponse(['message' => 'Invalid value'], 400);
        }
        $workers = $this->request->getParam('workers', null);
        if (!is_string($workers) || !preg_match('/^[1-3]$/D', $workers)) {
            return new JSONResponse(['message' => 'Invalid worker limit'], 400);
        }
        $this->config->setAppValue(PanoramaCache::APP_ID, PanoramaCache::ENABLED_KEY, $enabled);
        $this->config->setAppValue(PanoramaCache::APP_ID, PanoramaCache::WORKERS_KEY, $workers);
        return new JSONResponse(['enabled' => $this->enabled(), 'workers' => $this->workers()]);
    }

    private function workers(): int {
        return max(1, min(PanoramaCache::MAX_WORKERS, (int)$this->config->getAppValue(PanoramaCache::APP_ID, PanoramaCache::WORKERS_KEY, '1')));
    }

    private function enabled(): bool {
        return $this->config->getAppValue(PanoramaCache::APP_ID, PanoramaCache::ENABLED_KEY, 'yes') === 'yes';
    }
}
