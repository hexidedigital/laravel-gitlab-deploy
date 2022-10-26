<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Variable;
use Illuminate\Contracts\Filesystem\Filesystem;

final class GenerateSshKeysOnLocalhost extends BaseTask implements Task
{
    protected string $name = 'generate ssh keys - private key to gitlab (localhost)';

    public function __construct(
        private readonly Filesystem $filesystem,
    )
    {
    }

    public function execute(): void
    {
        $this->forceExecuteCommand('mkdir -p '.$this->replacements->replace(config('gitlab-deploy.ssh.folder')));

        if (!$this->isSshFilesExits() || $this->confirmAction('Should generate and override existed key?')) {
            $option = $this->isSshFilesExits() ? '-y' : '';
            $this->optionallyExecuteCommand('ssh-keygen -t rsa -f "{{IDENTITY_FILE}}" -N "" '.$option);
        }

        $this->logger->appendEchoLine($this->replacements->replace('cat {{IDENTITY_FILE}}'), 'info');

        $content = $this->filesystem->get($this->replacements->replace('{{IDENTITY_FILE}}'));

        $variable = new Variable(
            key: 'SSH_PRIVATE_KEY',
            scope: $this->state->getStage()->name,
            value: $content
        );

        $this->state->getGitlabVariablesBag()->add($variable->key, $variable);
    }

    private function isSshFilesExits(): bool
    {
        return $this->filesystem->exists($this->replacements->replace('{{IDENTITY_FILE}}'))
            || $this->filesystem->exists($this->replacements->replace('{{IDENTITY_FILE_PUB}}'));
    }
}
