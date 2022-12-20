<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Support\Facades\File;

final class GenerateSshKeysOnLocalhost extends BaseTask implements Task
{
    protected string $name = '🔑 Generate ssh-keys on localhost';

    public function execute(Pipedata $pipeData): void
    {
        $this->ensureDirectoryExists();

        if ($this->checkExistedKeys()) {
            $option = $this->isSshFilesExits() ? '-y' : '';

            $this->getExecutor()->runCommand('ssh-keygen -t rsa -f "{{IDENTITY_FILE}}" -N "" ' . $option);
        }

        $this->updatePrivateKeyVariable();
    }

    private function ensureDirectoryExists(): void
    {
        $this->getLogger()->line('Ensure directory exists.');

        $path = $this->getReplacements()->replace(config('gitlab-deploy.ssh.folder'));

        $this->getLogger()->line("mkdir -p $path", 'info');

        File::ensureDirectoryExists($path);
    }

    private function updatePrivateKeyVariable(): void
    {
        $this->getLogger()->line($this->getReplacements()->replace('cat {{IDENTITY_FILE}}'), 'info');

        $content = $this->identityFileContent();

        $variable = new Variable(
            key: 'SSH_PRIVATE_KEY',
            scope: $this->getState()->getStage()->name,
            value: $content
        );

        $this->getState()->getGitlabVariablesBag()->add($variable);
    }

    private function checkExistedKeys(): bool
    {
        return !$this->isSshFilesExits()
            || $this->confirmAction('Should generate and override existed key?');
    }

    private function isSshFilesExits(): bool
    {
        return File::exists($this->getReplacements()->replace('{{IDENTITY_FILE}}'))
            || File::exists($this->getReplacements()->replace('{{IDENTITY_FILE_PUB}}'));
    }

    private function identityFileContent(): string
    {
        $path = $this->getReplacements()->replace('{{IDENTITY_FILE}}');

        return $this->getContent($path);
    }
}
