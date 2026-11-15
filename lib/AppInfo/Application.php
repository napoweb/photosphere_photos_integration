<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\AppInfo;

use OCA\PhotospherePhotosIntegration\Listener\AddPhotosScriptListener;
use OCP\AppFramework\App;
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
        $context->registerEventListener(PublicShareTemplateRenderedEvent::class, AddPhotosScriptListener::class);
    }

    public function boot(IBootContext $context): void {
    }
}
