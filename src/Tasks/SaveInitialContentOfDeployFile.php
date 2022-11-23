<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Filesystem\Filesystem;

final class SaveInitialContentOfDeployFile extends BaseTask implements Task
{
    protected string $name = 'save initial content of deploy file';

    public function __construct(
        private readonly Filesystem $filesystem
    ) {
    }

    public function execute(Pipedata $pipeData): void
    {
        $path = config('gitlab-deploy.deployer-php');

        $this->filesystem->copy($path, $path . '.tmp');
    }
}
