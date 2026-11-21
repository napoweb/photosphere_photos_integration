<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\BackgroundJob;

use OCA\PhotospherePhotosIntegration\Service\UnusedPanoramaCleanup;
use OCP\BackgroundJob\TimedJob;
use OCP\BackgroundJob\IJob;
use OCP\AppFramework\Utility\ITimeFactory;

/** Once per day, conservatively prune unreferenced tour-only derivatives. */
final class CleanupUnusedPanoramaCacheJob extends TimedJob {
    public function __construct(ITimeFactory $time, private UnusedPanoramaCleanup $cleanup) {
        parent::__construct($time);
        $this->setInterval(86400);
        $this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
    }

    protected function run($argument): void {
        $this->cleanup->run();
    }
}
