<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class CopySshKeysOnRemoteHost extends BaseTask implements Task
{
    protected string $name = '📋 Copy ssh to remote server (public key)';

    public function execute(Pipedata $pipeData): void
    {
        $this->getLogger()->appendEchoLine(
            $this->getReplacements()->replace('Can ask a password, enter <info>{{DEPLOY_PASS}}</info>')
        );

        $this->confirmAction('Copy ssh keys to remote?', false)
        && $this->getExecutor()->runCommand('ssh-copy-id {{remoteSshCredentials}}');
    }
}
