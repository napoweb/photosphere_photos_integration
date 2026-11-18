<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

final class Admin implements ISettings {
    public function __construct(private IURLGenerator $urlGenerator) {}

    public function getForm(): TemplateResponse {
        Util::addScript('photosphere_photos_integration', 'watermark-settings');
        $route = fn(string $method) => $this->urlGenerator->linkToRoute('photosphere_photos_integration.watermark.' . $method);
        return new TemplateResponse('photosphere_photos_integration', 'admin', [
            'imageUrl' => $route('get'),
            'assignFolderUrl' => $route('assignFolder'),
            'folderOwnersUrl' => $route('folderOwners'),
            'browseFoldersUrl' => $route('browseFolders'),
            'unassignFolderUrl' => $route('unassignFolder'),
            'previewBaseUrl' => $route('preview'),
            'listUrl' => $route('listImages'),
            'uploadUrl' => $route('upload'),
            'resetUrl' => $route('reset'),
            'restoreDefaultUrl' => $route('restoreDefault'),
            'selectUrl' => $route('select'),
            'deleteUrl' => $route('delete'),
            'optionsUrl' => $route('saveOptions'),
            'zoomSettingsUrl' => $this->urlGenerator->linkToRoute('photosphere_photos_integration.zoom.settings'),
            'zoomSaveUrl' => $this->urlGenerator->linkToRoute('photosphere_photos_integration.zoom.save'),
        ], '');
    }

    public function getSection(): string { return 'photosphere_photos_integration'; }
    public function getPriority(): int { return 10; }
}
