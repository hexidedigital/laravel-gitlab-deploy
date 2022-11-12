<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Variable;

final class GenerateSshKeysOnRemoteHost extends BaseTask implements Task
{
    protected string $name = 'Generate generate ssh-keys on remote host';

    public function execute(): void
    {
        if ($this->confirmAction('Generate ssh keys on remote host')) {
            $this->executor->runCommand('ssh {{remoteSshCredentials}} "ssh-keygen -t rsa -f ~/.ssh/id_rsa -N \"\""');
        }

        $pubKeyContent = '';
        $this->executor->runCommand(
            'ssh {{remoteSshCredentials}} "cat ~/.ssh/id_rsa.pub"',
            function ($type, $buffer) use (&$pubKeyContent) {
                $pubKeyContent = $buffer;
            }
        );

        $pubKeyVariable = new Variable(
            key: 'SSH_PUB_KEY',
            scope: $this->state->getStage()->name,
            value: $pubKeyContent
        );

        $this->state->getGitlabVariablesBag()->add($pubKeyVariable->key, $pubKeyVariable);

        $this->logger->appendEchoLine('Remote pub-key: '.$pubKeyVariable->value, 'info');
    }
}