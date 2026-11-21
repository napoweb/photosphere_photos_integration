<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

final class Personal implements ISettings {
    public function __construct(private IURLGenerator $urlGenerator) {}

    public function getForm(): TemplateResponse {
        Util::addScript('photosphere_photos_integration', 'personal-redirect');
        return new TemplateResponse('photosphere_photos_integration', 'personal', [
            'tourUrl' => $this->urlGenerator->linkToRoute('photosphere_photos_integration.tour.index'),
        ], '');
    }
    public function getSection(): string { return 'photosphere_tours'; }
    public function getPriority(): int { return 10; }
}
