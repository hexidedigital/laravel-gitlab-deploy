<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class SaveInitialContentOfDeployFile extends BaseTask implements Task
{
    protected string $name = '💾 Save initial content of deploy file';

    public function execute(Pipedata $pipeData): void
    {
        $path = config('gitlab-deploy.deployer-php');

        $this->copyFile($path, $path . '.tmp');
    }
}
