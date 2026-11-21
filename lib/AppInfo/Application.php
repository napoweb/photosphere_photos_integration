<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\AppInfo;

use OCA\PhotospherePhotosIntegration\Listener\AddPhotosScriptListener;
use OCA\PhotospherePhotosIntegration\Listener\TourNavigationListener;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
use OCP\AppFramework\App;
use OCP\BackgroundJob\IJobList;
use OCA\PhotospherePhotosIntegration\BackgroundJob\CleanupUnusedPanoramaCacheJob;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent as PublicShareTemplateRenderedEvent;

class Application extends App implements IBootstrap {
    public const APP_ID = 'photosphere_photos_integration';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(BeforeTemplateRenderedEvent::class, AddPhotosScriptListener::class);
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, TourNavigationListener::class);
        $context->registerEventListener(PublicShareTemplateRenderedEvent::class, AddPhotosScriptListener::class);
    }

    public function boot(IBootContext $context): void {
        // Register a recurring job without requiring an admin to run manual
        // installation commands. The scheduler survives an upgrade and also
        // works when the last tour is removed.
        try {
            $jobs = $context->getServerContainer()->get(IJobList::class);
            if (!$jobs->has(CleanupUnusedPanoramaCacheJob::class, null)) {
                $jobs->add(CleanupUnusedPanoramaCacheJob::class);
            }
        } catch (\Throwable $e) {
            // Never interfere with serving existing Nextcloud requests.
        }
    }
}
