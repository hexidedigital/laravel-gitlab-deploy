<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Variable;
use Illuminate\Contracts\Filesystem\Filesystem;

final class CopySshKeysOnRemoteHost extends BaseTask implements Task
{
    protected string $name = 'copy ssh to server - public key to remote host';

    public function execute(): void
    {
        $this->logger->appendEchoLine($this->replacements->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>'));

        $this->executor->runCommand('ssh-copy-id {{remoteSshCredentials}}');
    }
}
