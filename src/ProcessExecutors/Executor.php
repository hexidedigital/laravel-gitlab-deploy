<?php

namespace HexideDigital\GitlabDeploy\ProcessExecutors;

use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\Loggers\LoggerBag;

abstract class Executor
{
    public function __construct(
        protected readonly LoggerBag $logger,
        protected readonly Replacements $replacements,
    ) {
    }

    public function runCommand(string $command, callable $callable = null): void
    {
        $command = $this->prepareCommand($command);

        $this->logger->line(
            <<<HTML
<span class="text-info">Command:</span> <span class="text-command">$command</span>
HTML
        );

        $this->execute($command, $callable);
    }

    protected function prepareCommand(string $command): string
    {
        return $this->replacements->replace($command);
    }

    /**
     * @param string $command
     * @param callable|null $callable
     * @return void
     */
    abstract protected function execute(string $command, ?callable $callable): void;
}
