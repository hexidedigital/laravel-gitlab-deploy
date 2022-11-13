<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

final class Options implements BaseOption
{
    public readonly string $gitUrl;
    public readonly string $baseDir;
    public readonly string $binPhp;
    public readonly string $binComposer;

    public function __construct(array $source)
    {
        $this->gitUrl = data_get($source, 'git-url');
        $this->baseDir = data_get($source, 'base-dir-pattern');
        $this->binPhp = data_get($source, 'bin-php');
        $this->binComposer = data_get($source, 'bin-composer');
    }

    public function toArray(): array
    {
        return [
            'gitUrl' => $this->gitUrl,
            'baseDir' => $this->baseDir,
            'binPhp' => $this->binPhp,
            'binComposer' => $this->binComposer,
        ];
    }

    public function toReplacesArray(): array
    {
        return [
            '{{CI_REPOSITORY_URL}}' => $this->gitUrl,
            '{{DEPLOY_BASE_DIR}}' => $this->baseDir,
            '{{BIN_PHP}}' => $this->binPhp,
            '{{BIN_COMPOSER}}' => $this->binComposer,
        ];
    }
}
