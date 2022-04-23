<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeployOptions;

class Options extends OptionsContainer
{
    public string $gitUrl;
    public string $baseDir;
    public string $binPhp;
    public string $binComposer;

    public function makeFromSource(array $source)
    {
        $this->gitUrl = data_get($source, 'git-url');
        $this->baseDir = data_get($source, 'base-dir-pattern');
        $this->binPhp = data_get($source, 'bin-php');
        $this->binComposer = data_get($source, 'bin-composer');
    }

    public function toArray(): array
    {
        return [
            '{{CI_REPOSITORY_URL}}' => $this->gitUrl,
            '{{DEPLOY_BASE_DIR}}' => $this->baseDir,
            '{{BIN_PHP}}' => $this->binPhp,
            '{{BIN_COMPOSER}}' => $this->binComposer,
        ];
    }
}
