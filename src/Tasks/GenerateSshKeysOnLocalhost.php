<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Contracts\Filesystem\Filesystem;

final class GenerateSshKeysOnLocalhost extends BaseTask implements Task
{
    protected string $name = 'generate ssh keys - private key to gitlab (localhost)';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function execute(Pipedata $pipeData): void
    {
        $this->ensureDirectoryExists();

        if ($this->checkExistedKeys()) {
            $option = $this->isSshFilesExits() ? '-y' : '';

            $this->getExecutor()->runCommand('ssh-keygen -t rsa -f "{{IDENTITY_FILE}}" -N "" ' . $option);
        }

        $this->updatePrivateKeyVariable();
    }

    public function ensureDirectoryExists(): void
    {
        $path = $this->getReplacements()->replace(config('gitlab-deploy.ssh.folder'));

        $this->getLogger()->appendEchoLine("mkdir -p $path");
        $this->filesystem->makeDirectory($path);
    }

    public function updatePrivateKeyVariable(): void
    {
        $this->getLogger()->appendEchoLine($this->getReplacements()->replace('cat {{IDENTITY_FILE}}'), 'info');

        $content = $this->filesystem->get($this->getReplacements()->replace('{{IDENTITY_FILE}}'));

        $variable = new Variable(
            key: 'SSH_PRIVATE_KEY',
            scope: $this->getState()->getStage()->name,
            value: $content
        );

        $this->getState()->getGitlabVariablesBag()->add($variable);
    }

    public function checkExistedKeys(): bool
    {
        return !$this->isSshFilesExits()
            || $this->confirmAction('Should generate and override existed key?');
    }

    private function isSshFilesExits(): bool
    {
        return $this->filesystem->exists($this->getReplacements()->replace('{{IDENTITY_FILE}}'))
            || $this->filesystem->exists($this->getReplacements()->replace('{{IDENTITY_FILE_PUB}}'));
    }
}
