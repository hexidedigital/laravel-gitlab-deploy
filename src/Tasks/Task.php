<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\Executors\Executor;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Console\Command;

interface Task
{
    public function setState(DeployerState $state): void;

    public function setLogger(BasicLogger $logger): void;

    public function setExecutor(Executor $executor): void;

    public function setCommand(Command $command): void;

    public function getTaskName(): string;

    public function shouldRunInPrintMode(): bool;

    public function canBeSkipped(): bool;

    public function execute(PipeData $pipeData): void;

    public function handle(PipeData $pipeData, callable $next): mixed;
}
