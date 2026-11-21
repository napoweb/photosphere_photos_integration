<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
use OCP\IConfig;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

/** Adds the optional, administrator-controlled tour entry for logged-in users. */
final class TourNavigationListener implements IEventListener {
    private const APP_ID = 'photosphere_photos_integration';
    private const ENABLED_KEY = 'tour_navigation_enabled';

    public function __construct(
        private IConfig $config,
        private INavigationManager $navigation,
        private IURLGenerator $urlGenerator,
        private IUserSession $userSession,
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalEntriesEvent) { return; }
        if ($this->userSession->getUser() === null) { return; }
        if ($this->config->getAppValue(self::APP_ID, self::ENABLED_KEY, 'no') !== 'yes') { return; }
        $this->navigation->add([
            'id' => 'photosphere_tours',
            'app' => self::APP_ID,
            'name' => '360°-Touren',
            'href' => $this->urlGenerator->linkToRoute(self::APP_ID . '.tour.index'),
            'icon' => $this->urlGenerator->imagePath(self::APP_ID, 'tours.svg'),
            'order' => 80,
            'type' => 'link',
        ]);
    }
}
