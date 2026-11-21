<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\BackgroundJob;

use OCA\PhotospherePhotosIntegration\Service\PanoramaCache;
use OCP\BackgroundJob\QueuedJob;
use OCP\AppFramework\Utility\ITimeFactory;

/** Lightweight per-user cleanup, queued at most once per day on tour use. */
final class CleanupPanoramaCacheJob extends QueuedJob {
    public function __construct(ITimeFactory $time, private PanoramaCache $cache) {
        parent::__construct($time);
    }

    protected function run($argument): void {
        if (!is_array($argument)) { return; }
        $this->cache->cleanupForUser((string)($argument['uid'] ?? ''));
    }
}
