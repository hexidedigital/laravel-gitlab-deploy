<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use Illuminate\Contracts\Filesystem\Filesystem;

final class DeployerFileContent
{
    private readonly string $content;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $path,
    )
    {
    }

    /**
     * @throws GitlabDeployException
     */
    public function backup(): void
    {
        $content = $this->filesystem->get($this->path);

        if (empty($content)) {
            throw new GitlabDeployException('Deploy file is empty or not exists.');
        }

        $this->content = $content;
    }

    public function restore(): void
    {
        $this->filesystem->put(config('gitlab-deploy.deployer-php'), $this->content);
    }
}
