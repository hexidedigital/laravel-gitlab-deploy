<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\ProcessExecutors\Executor;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;

abstract class BaseTask implements Task
{
    protected string $name;

    protected PipeData $pipeData;
    protected Stage $stage;

    protected Configurations $configurations;
    protected Replacements $replacements;
    protected BasicLogger $logger;
    protected DeployerState $state;
    protected Executor $executor;
    protected Command $command;

    public function processPipeData(PipeData $pipeData): void
    {
        $this->processDeployerState($pipeData->state);

        $this->pipeData = $pipeData;
        $this->logger = $pipeData->logger;
        $this->executor = $pipeData->executor;
    }

    public function processDeployerState(DeployerState $state): void
    {
        $this->state = $state;
        $this->replacements = $state->getReplacements();
        $this->configurations = $state->getConfigurations();
        $this->stage = $state->getStage();
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

    public function handle(PipeData $pipeData, callable $next): mixed
    {
        $this->processPipeData($pipeData);

        if ($this->isPrintOnly() && !$this->shouldRunInPrintMode()) {
            return $next($pipeData);
        }

        $this->getLogger()->newSection($pipeData->incrementStepNumber(), $this->getTaskName());

        $this->execute($pipeData);

        return $next($pipeData);
    }


    /**
     * @return PipeData
     */
    public function getPipeData(): PipeData
    {
        return $this->pipeData;
    }

    /**
     * @return Stage
     */
    public function getStage(): Stage
    {
        return $this->stage;
    }

    /**
     * @return Configurations
     */
    public function getConfigurations(): Configurations
    {
        return $this->configurations;
    }

    /**
     * @return Replacements
     */
    public function getReplacements(): Replacements
    {
        return $this->replacements;
    }

    /**
     * @return BasicLogger
     */
    public function getLogger(): BasicLogger
    {
        return $this->logger;
    }

    /**
     * @return DeployerState
     */
    public function getState(): DeployerState
    {
        return $this->state;
    }

    /**
     * @return Executor
     */
    public function getExecutor(): Executor
    {
        return $this->executor;
    }

    /**
     * @return Command
     */
    public function getCommand(): Command
    {
        return $this->command;
    }

    public function isPrintOnly(): bool
    {
        return $this->getState()->isPrintOnly();
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
        if ($this->isPrintOnly()) {
            return;
        }

        $contents = $this->replacements->replace($filesystem->get($path), $replaces);

        $filesystem->put($path, $contents);
    }
}
