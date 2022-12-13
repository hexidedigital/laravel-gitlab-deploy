<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\PipeData;
use HexideDigital\GitlabDeploy\ProcessExecutors\Executor;
use Illuminate\Console\Command;

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
        $this->command = $pipeData->command;
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

    public function writeContent(string $path, string $contents): void
    {
        $this->getLogger()->appendEchoLine("Updating content for file: <comment>$path</comment>");

        if ($this->isPrintOnly()) {
            return;
        }

        \File::put($path, $contents);
    }

    public function getContent(string $path): string
    {
        $this->getLogger()->appendEchoLine("Reading content from file: <comment>$path</comment>");

        if ($this->isPrintOnly()) {
            return '';
        }

        return \File::get($path) ?: '';
    }

    /**
     * @param string $from
     * @param mixed $to
     * @return void
     */
    public function copyFile(string $from, mixed $to): void
    {
        $this->getLogger()->appendEchoLine("Coping files: from [<comment>$from</comment>], to [<comment>$to</comment>]");

        if ($this->isPrintOnly()) {
            return;
        }

        \File::copy($from, $to);
    }

    protected function confirmAction(string $question, bool $default = false): bool
    {
        if (!isset($this->command)) {
            return $default;
        }

        if ($this->isPrintOnly()) {
            return $default;
        }


        return $this->command->confirm($question, $default);
    }

    protected function writeContentWithReplaces(string $path, array $patterns): void
    {
        if ($this->isPrintOnly()) {
            return;
        }

        $contents = $this->getContent($path);

        foreach ($patterns as $pattern => $replacement) {
            $replacement = $this->replacements->replace($replacement);

            $contents = preg_replace("/$pattern/m", $replacement, $contents);
        }

        $this->writeContent($path, $contents);
    }
}
