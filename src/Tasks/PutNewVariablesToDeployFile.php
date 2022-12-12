<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class PutNewVariablesToDeployFile extends BaseTask implements Task
{
    protected string $name = 'putting static env variables to deploy file';

    public function execute(PipeData $pipeData): void
    {
        $env = $this->getReplacements()->replace('{{DEPLOY_PHP_ENV}}');

        $this->getLogger()->appendEchoLine($env);

        $path = config('gitlab-deploy.deployer-php');

        $patterns = [
            '/*CI_ENV*/' => $env,
            '~/.ssh/id_rsa' => $this->getReplacements()->replace('{{IDENTITY_FILE}}'),
        ];

        $this->updateWithPatternReplaces($path, $patterns);
    }
}
