<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\ProcessExecutors\Executor;
use Illuminate\Console\Command;

class PipeData
{
    protected int $stepNumber = 1;

    public function __construct(
        public readonly DeployerState $state,
        public readonly BasicLogger $logger,
        public readonly Executor $executor,
        public readonly Command $command,
        public readonly int $totalSteps,
    ) {
    }

    final public function incrementStepNumber(): int
    {
        return $this->stepNumber++;
    }
}
