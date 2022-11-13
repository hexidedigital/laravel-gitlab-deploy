<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\Executors\Executor;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;

class PipeData
{
    public function __construct(
        public readonly DeployerState $state,
        public readonly BasicLogger $logger,
        public readonly Executor $executor,
    ) {
    }
}
