<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\Loggers\LoggerBag;
use HexideDigital\GitlabDeploy\PipeData;
use HexideDigital\GitlabDeploy\ProcessExecutors\Executor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

abstract class BaseTask implements Task
{
    protected string $name;

    protected PipeData $pipeData;
    protected Stage $stage;

    protected Configurations $configurations;
    protected Replacements $replacements;
    protected LoggerBag $logger;
    protected DeployerState $state;
    protected Executor $executor;
    protected ?Command $command = null;

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

        $this->getLogger()->newSection(
            $pipeData->incrementStepNumber(),
            $this->getTaskName(),
            $pipeData->totalSteps
        );

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
     * @return LoggerBag
     */
    public function getLogger(): LoggerBag
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
        $this->getLogger()->line(
            <<<HTML
Updating content for file: <span class="text-orange-500">$path</span>
HTML
        );

        if ($this->isPrintOnly()) {
            return;
        }

        File::ensureDirectoryExists(File::dirname($path));
        File::put($path, $contents);
    }

    public function getContent(string $path): string
    {
        $this->getLogger()->line(
            <<<HTML
Reading content from file: <span class="text-lime-500">$path</span>
HTML
        );

        if ($this->isPrintOnly()) {
            return '';
        }

        return File::get($path) ?: '';
    }

    public function copyFile(string $from, string $to): void
    {
        $this->getLogger()->line(
            <<<HTML
Coping files: from [<span class="text-lime-500">$from</span>], to [<span class="text-orange-500">$to</span>]
HTML
        );

        if ($this->isPrintOnly()) {
            return;
        }

        File::copy($from, $to);
    }

    public function removeFile(string $path): void
    {
        $this->getLogger()->line(
            <<<HTML
Deleting path: <span class="text-red-500">$path</span>
HTML
        );

        if ($this->isPrintOnly()) {
            return;
        }

        File::delete($path);
    }

    protected function confirmAction(string $question, bool $default = false): bool
    {
        if (!isset($this->command)) {
            return $default;
        }

        if ($this->isPrintOnly()) {
            return $default;
        }

        $this->getLogger()->getConsoleLogger()->line("<div class='text-blue-500 mt-1'>$question</div>");

        return $this->command->confirm('', $default);
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

    protected function canAskPassword(): void
    {
        $this->getLogger()->line(
            $this->getReplacements()->replace(
                view('gitlab-deploy::console.can-ask-password')->render()
            )
        );
    }

    protected function skipping(string $content = ''): void
    {
        $prefix = $content ? ' - ' : '';
        $content = $prefix . $content;
        $this->getLogger()->line(
            <<<HTML
<span class="text-gray italic">Skipped{$content}.</span>
HTML
        );
    }
}
