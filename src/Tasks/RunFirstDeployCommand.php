<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class RunFirstDeployCommand extends BaseTask implements Task
{
    protected string $name = '🚀 Run deploy from local';

    public function execute(Pipedata $pipeData): void
    {
        $fileExists = $this->confirmAction(
            'Please, check if the file was copied correctly to remote host. It is right?',
            true
        );

        if (!$fileExists) {
            // option only print disabled
            // and file not copied
            $this->getLogger()->appendEchoLine('The deployment command was skipped.', 'error');

            return;
        }

        $this->getExecutor()->runCommand(
            'php {{PROJ_DIR}}/vendor/bin/dep deploy',
            function ($type, $buffer) {
                $this->getLogger()->appendEchoLine($type . ' > ' . trim($buffer));
            }
        );
    }
}
