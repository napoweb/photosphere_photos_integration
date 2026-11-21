<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Settings;

use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

final class PersonalSection implements IIconSection {
    public function __construct(private IURLGenerator $urlGenerator) {}

    public function getID(): string { return 'photosphere_tours'; }
    public function getName(): string { return '360° Touren'; }
    public function getPriority(): int { return 75; }
    public function getIcon(): string {
        return $this->urlGenerator->imagePath('photosphere_photos_integration', 'app.svg');
    }
}
