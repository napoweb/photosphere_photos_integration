<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

/** Only Nextcloud administrators can read or update this global navigation option. */
final class TourNavigationController extends Controller {
    private const APP_ID = 'photosphere_photos_integration';
    private const ENABLED_KEY = 'tour_navigation_enabled';

    public function __construct(IRequest $request, private IConfig $config) {
        parent::__construct(self::APP_ID, $request);
    }

    #[NoCSRFRequired]
    public function status(): JSONResponse {
        return new JSONResponse(['enabled' => $this->enabled()]);
    }

    // Nextcloud retains its normal admin-only and CSRF validation for this POST.
    public function save(): JSONResponse {
        $enabled = $this->request->getParam('enabled', null);
        if (!in_array($enabled, ['yes', 'no'], true)) {
            return new JSONResponse(['message' => 'Ungültiger Wert'], 400);
        }
        $this->config->setAppValue(self::APP_ID, self::ENABLED_KEY, $enabled);
        return new JSONResponse(['enabled' => $this->enabled()]);
    }

    private function enabled(): bool {
        return $this->config->getAppValue(self::APP_ID, self::ENABLED_KEY, 'no') === 'yes';
    }
}
