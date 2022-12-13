<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

interface Task
{
    public function getTaskName(): string;

    public function shouldRunInPrintMode(): bool;

    public function execute(PipeData $pipeData): void;

    public function handle(PipeData $pipeData, callable $next): mixed;
}
