<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

final class AdminSection implements IIconSection {
    public function __construct(private IL10N $l, private IURLGenerator $urlGenerator) {}

    public function getID(): string {
        return 'photosphere_photos_integration';
    }

    public function getName(): string {
        return '360° Panorama';
    }

    public function getPriority(): int {
        return 75;
    }

    public function getIcon(): string {
        return $this->urlGenerator->imagePath('core', 'actions/settings-dark.svg');
    }
}
