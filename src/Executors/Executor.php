<?php

namespace HexideDigital\GitlabDeploy\Executors;

use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use Symfony\Component\Process\Process;

class Executor
{
    public function __construct(
        private readonly BasicLogger  $logger,
        private readonly Replacements $replacements,
        private readonly bool         $isOnlyPrint,
    ) {
    }

    public function runCommand(string $command, callable $callable = null): void
    {
        $command = $this->replacements->replace($command);

        $this->logger->appendEchoLine($command, 'info');

        if ($this->isOnlyPrint()) {
            return;
        }

        $this->logger->appendEchoLine('running command...'.PHP_EOL);

        $process = Process::fromShellCommandline($command);
        $process->run($callable);
    }

    private function isOnlyPrint(): bool
    {
        return $this->isOnlyPrint;
    }
}
