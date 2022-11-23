<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class RunFirstDeployCommand extends BaseTask implements Task
{
    protected string $name = 'run deploy from local';

    public function execute(Pipedata $pipeData): void
    {
        $fileExists = $this->confirmAction(
            'Please, check if the file was copied correctly to remote host. It is right?',
            true
        );

        if (!$fileExists) {
            // option only print disabled
            // and file not copied
            $this->logger->appendEchoLine('The deployment command was skipped.', 'error');

            return;
        }

        $this->executor->runCommand(
            'php {{PROJ_DIR}}/vendor/bin/dep deploy',
            function ($type, $buffer) {
                $this->logger->appendEchoLine($type . ' > ' . trim($buffer));
            }
        );
    }
}
