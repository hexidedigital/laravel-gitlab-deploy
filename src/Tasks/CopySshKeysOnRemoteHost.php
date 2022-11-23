<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class CopySshKeysOnRemoteHost extends BaseTask implements Task
{
    protected string $name = 'copy ssh to server - public key to remote host';

    public function execute(Pipedata $pipeData): void
    {
        $this->getLogger()->appendEchoLine(
            $this->getReplacements()->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>')
        );

        $this->getExecutor()->runCommand('ssh-copy-id {{remoteSshCredentials}}');
    }
}
