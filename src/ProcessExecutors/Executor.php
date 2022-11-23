<?php

namespace HexideDigital\GitlabDeploy\ProcessExecutors;

use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\Replacements;

abstract class Executor
{
    public function __construct(
        protected readonly BasicLogger  $logger,
        protected readonly Replacements $replacements,
    ) {
    }

    public function runCommand(string $command, callable $callable = null): void
    {
        $command = $this->prepareCommand($command);

        $this->logInfo($command);

        $this->execute($command, $callable);
    }

    /**
     * @param string $command
     * @return void
     */
    protected function logInfo(string $command): void
    {
        $this->logger->appendEchoLine($command, 'info');
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
