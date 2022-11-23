<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Executors\Executor;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;

abstract class BaseTask implements Task
{
    protected string $name;

    protected Configurations $configurations;
    protected Replacements $replacements;
    protected Stage $stage;
    protected BasicLogger $logger;
    protected DeployerState $state;
    protected Executor $executor;
    protected Command $command;

    public function setState(DeployerState $state): void
    {
        $this->state = $state;
        $this->replacements = $state->getReplacements();
        $this->configurations = $state->getConfigurations();
        $this->stage = $state->getStage();
    }

    public function setLogger(BasicLogger $logger): void
    {
        $this->logger = $logger;
    }

    public function setExecutor(Executor $executor): void
    {
        $this->executor = $executor;
    }

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    public function getTaskName(): string
    {
        return $this->name;
    }

    public function shouldRunInPrintMode(): bool
    {
        return true;
    }

    public function canBeSkipped(): bool
    {
        return true;
    }

    public function handle(PipeData $pipeData, callable $next): mixed
    {
        if (
            $this->canBeSkipped()
            || !$this->shouldRunInPrintMode()
        ) {
            return $next($pipeData);
        }

        $this->logger->newSection($pipeData->incrementStepNumber(), $this->getTaskName());

        $this->execute($pipeData);

        return $next($pipeData);
    }

    protected function confirmAction(string $question, bool $default = false): bool
    {
        if (!isset($this->command)) {
            return $default;
        }

        return $this->command->confirm($question, $default);
    }

    protected function updateWithReplaces(Filesystem $filesystem, string $path, array $replaces = null): void
    {
//        if ($this->isOnlyPrint()) {
//            return;
//        }

        $contents = $this->replacements->replace($filesystem->get($path), $replaces);

        $filesystem->put($path, $contents);
    }
}
