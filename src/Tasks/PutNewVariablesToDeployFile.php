<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Contracts\Filesystem\Filesystem;

final class PutNewVariablesToDeployFile extends BaseTask implements Task
{
    protected string $name = 'putting static env variables to deploy file';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function execute(PipeData $pipeData): void
    {
        $env = $this->getReplacements()->replace('{{DEPLOY_PHP_ENV}}');

        $this->getLogger()->appendEchoLine($env);

        $path = config('gitlab-deploy.deployer-php');

        $replaces = [
            '/*CI_ENV*/' => $env,
            '~/.ssh/id_rsa' => $this->getReplacements()->replace('{{IDENTITY_FILE}}'),
        ];

        $this->updateWithReplaces($this->filesystem, $path, $replaces);
    }
}
