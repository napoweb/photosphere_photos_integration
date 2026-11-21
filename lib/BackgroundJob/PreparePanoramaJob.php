<?php

declare(strict_types=1);

namespace OCA\PhotospherePhotosIntegration\BackgroundJob;

use OCA\PhotospherePhotosIntegration\Service\PanoramaCache;
use OCP\BackgroundJob\QueuedJob;
use OCP\AppFramework\Utility\ITimeFactory;

final class PreparePanoramaJob extends QueuedJob {
    public function __construct(ITimeFactory $time, private PanoramaCache $cache) {
        parent::__construct($time);
    }

    protected function run($argument): void {
        if (!is_array($argument)) { return; }
        $this->cache->warm((int)($argument['fileId'] ?? 0), (string)($argument['uid'] ?? ''));
    }
}
