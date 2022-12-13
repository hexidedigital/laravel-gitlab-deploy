<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\PipeData;

final class GenerateSshKeysOnRemoteHost extends BaseTask implements Task
{
    protected string $name = 'Generate generate ssh-keys on remote host';

    public function execute(Pipedata $pipeData): void
    {
        if ($this->confirmAction('Generate ssh keys on remote host?')) {
            $this->getExecutor()->runCommand('ssh {{remoteSshCredentials}} "ssh-keygen -t rsa -f ~/.ssh/id_rsa -N \"\""');
        }

        $pubKeyContent = '';
        $this->getExecutor()->runCommand(
            'ssh {{remoteSshCredentials}} "cat ~/.ssh/id_rsa.pub"',
            function ($type, $buffer) use (&$pubKeyContent) {
                $pubKeyContent = $buffer;
            }
        );

        $pubKeyVariable = new Variable(
            key: 'SSH_PUB_KEY',
            scope: $this->getState()->getStage()->name,
            value: $pubKeyContent
        );

        $this->getState()->getGitlabVariablesBag()->add($pubKeyVariable);

        $this->getLogger()->appendEchoLine('Remote pub-key: <comment>' . $pubKeyVariable->value . '</comment>');
    }
}
