<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\ProcessExecutors\Executor;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use Illuminate\Console\Command;

class PipeData
{
    protected int $stepNumber = 1;

    public function __construct(
        public readonly DeployerState $state,
        public readonly BasicLogger $logger,
        public readonly Executor $executor,
        public readonly Command $command,
    ) {
    }

    final public function incrementStepNumber(): int
    {
        return $this->stepNumber++;
    }
}
