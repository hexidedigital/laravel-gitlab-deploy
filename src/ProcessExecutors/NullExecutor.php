<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\ProcessExecutors;

final class NullExecutor extends Executor
{
    /**
     * @inheritDoc
     */
    protected function execute(string $command, ?callable $callable): void
    {
        $this->logger->appendEchoLine('running command...' . PHP_EOL);
    }
}
