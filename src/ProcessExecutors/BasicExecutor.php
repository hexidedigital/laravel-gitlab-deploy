<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\ProcessExecutors;

use Symfony\Component\Process\Process;

final class BasicExecutor extends Executor
{
    /**
     * @inheritDoc
     */
    protected function execute(string $command, ?callable $callable): void
    {
        $this->logger->getConsoleLogger()->line('<span class="italic">Running command...</span>' . PHP_EOL);

        $process = Process::fromShellCommandline($command);
        $process->run($callable);
    }
}
