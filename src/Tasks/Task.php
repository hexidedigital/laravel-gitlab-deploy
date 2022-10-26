<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;

interface Task
{
    public function setState(DeployerState $state): void;

    public function setLogger(BasicLogger $logger): void;

    public function getTaskName(): string;

    public function shouldRunInPrintMode(): bool;

    public function canBeSkipped(): bool;

    public function execute(): void;
}
