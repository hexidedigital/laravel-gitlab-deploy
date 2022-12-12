<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class RollbackDeployFileContent extends BaseTask implements Task
{
    protected string $name = 'Rollback deploy file content';

    public function execute(Pipedata $pipeData): void
    {
        $path = config('gitlab-deploy.deployer-php');

        $this->getLogger()->appendEchoLine("cp $path.tmp $path");

        if ($this->isPrintOnly()) {
            return;
        }

        \File::copy($path . '.tmp', $path);
    }
}
