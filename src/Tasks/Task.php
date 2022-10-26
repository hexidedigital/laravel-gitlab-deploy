<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\Replacements;

interface Task
{
    public function setConfigurations(Configurations $configurations): void;

    public function setReplacements(Replacements $replacements): void;

    public function setStage(Stage $stage): void;

    public function setLogger(BasicLogger $logger): void;

    public function getTaskName(): string;

    public function shouldRunInPrintMode(): bool;

    public function canBeSkipped(): bool;

    public function execute(): void;
}
