<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

/** Shared, non-sensitive zoom configuration; saving remains admin-only. */
final class ZoomController extends Controller {
    private const APP_ID = 'photosphere_photos_integration';
    private const DEFAULTS = [
        'desktopMin' => 60, 'desktopStart' => 110, 'desktopMax' => 135,
        'mobileMin' => 30, 'mobileStart' => 75, 'mobileMax' => 110,
    ];

    public function __construct(IRequest $request, private IConfig $config) {
        parent::__construct(self::APP_ID, $request);
    }

    private function options(): array {
        $raw = json_decode($this->config->getAppValue(self::APP_ID, 'zoom_options', '{}'), true);
        if (!is_array($raw)) { $raw = []; }
        $all = array_merge(self::DEFAULTS, array_intersect_key($raw, self::DEFAULTS));
        return $this->validate($all) ?? self::DEFAULTS;
    }

    private function validate(array $data): ?array {
        $out = [];
        foreach (self::DEFAULTS as $name => $default) {
            if (!isset($data[$name]) || !is_numeric($data[$name])) { return null; }
            $value = (float)$data[$name];
            if (!is_finite($value) || floor($value) !== $value) { return null; }
            $min = str_ends_with($name, 'Min') ? 20 : (str_ends_with($name, 'Max') ? 80 : 60);
            $max = str_ends_with($name, 'Min') ? 70 : (str_ends_with($name, 'Max') ? 150 : 145);
            if ($value < $min || $value > $max) { return null; }
            $out[$name] = (int)$value;
        }
        foreach (['desktop', 'mobile'] as $device) {
            if (!($out[$device . 'Min'] + 5 <= $out[$device . 'Start'] &&
                  $out[$device . 'Start'] + 5 <= $out[$device . 'Max'])) { return null; }
        }
        return $out;
    }

    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function settings(): JSONResponse {
        $response = new JSONResponse(['success' => true, 'zoom' => $this->options()]);
        $response->addHeader('Cache-Control', 'no-store, max-age=0');
        return $response;
    }

    /** No NoAdminRequired annotation: Nextcloud restricts this endpoint to admins. */
    public function save(): JSONResponse {
        $raw = $this->request->getParam('zoom', null);
        if (is_string($raw)) { $raw = json_decode($raw, true); }
        if (!is_array($raw) || ($values = $this->validate($raw)) === null) {
            return new JSONResponse(['success' => false, 'message' => 'Zoomwerte sind ungültig oder widersprüchlich.'], 400);
        }
        $this->config->setAppValue(self::APP_ID, 'zoom_options', json_encode($values));
        return new JSONResponse(['success' => true, 'zoom' => $values]);
    }
}
