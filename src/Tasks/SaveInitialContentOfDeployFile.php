<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class SaveInitialContentOfDeployFile extends BaseTask implements Task
{
    protected string $name = 'save initial content of deploy file';

    public function execute(Pipedata $pipeData): void
    {
        $path = config('gitlab-deploy.deployer-php');

        $this->getLogger()->appendEchoLine("cp $path $path.tmp");

        if ($this->isPrintOnly()) {
            return;
        }

        \File::copy($path, $path . '.tmp');
    }
}
