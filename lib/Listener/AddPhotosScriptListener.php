<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Listener;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent as PublicShareTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class AddPhotosScriptListener implements IEventListener {
    public function handle(Event $event): void {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $publicAlbum = (bool)preg_match('~/(?:index\.php/)?apps/photos/public(?:/|$)~', $path);
        $publicShare = (bool)preg_match('~/(?:index\.php/)?s/[^/]+(?:/|$)~', $path);
        $files = (bool)preg_match('~/(?:index\.php/)?apps/files(?:/|$)~', $path);

        if ($event instanceof PublicShareTemplateRenderedEvent) {
            // Do not inject on share-password / authentication forms.
            if (!$publicShare || $event->getScope() === PublicShareTemplateRenderedEvent::SCOPE_PUBLIC_SHARE_AUTH) {
                return;
            }
            // The viewer action is registered in public file/folder shares as well.
            Util::addInitScript('photosphere_photos_integration', 'files-guard');
            Util::addScript('photosphere_photos_integration', 'photosphere-photos');
            return;
        }
        if (!$event instanceof BeforeTemplateRenderedEvent) {
            return;
        }
        if (!$event->isLoggedIn() && !$publicAlbum && !$publicShare) {
            return;
        }
        if ($files || $publicShare) {
            Util::addInitScript('photosphere_photos_integration', 'files-guard');
        }
        // The UI code checks its route and never uses another user's credentials.
        Util::addScript('photosphere_photos_integration', 'photosphere-photos');
    }
}
