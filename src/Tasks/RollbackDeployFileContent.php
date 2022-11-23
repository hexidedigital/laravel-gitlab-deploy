<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Filesystem\Filesystem;

final class RollbackDeployFileContent extends BaseTask implements Task
{
    protected string $name = 'Rollback deploy file content';

    public function __construct(
        private readonly Filesystem $filesystem
    ) {
    }

    public function execute(Pipedata $pipeData): void
    {
        $path = config('gitlab-deploy.deployer-php');

        $this->filesystem->copy($path . '.tmp', $path);
    }
}
