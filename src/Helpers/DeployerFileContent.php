<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;

final class DeployerFileContent
{
    private readonly string $content;

    public function __construct(
        private readonly string $path,
    )
    {
    }

    /**
     * @throws GitlabDeployException
     */
    public function backup(bool $isOnlyPrint = false): void
    {
        dump(\File::dirname($this->path));

        dump(\File::files(base_path()));
//        dump(\File::put('temp11223344.ttxx', ''));

        dd($this->path, \File::get($this->path), \File::get(base_path('deploy.php')));

        $content = \File::get($this->path);

        if (empty($content) && !$isOnlyPrint) {
            throw new GitlabDeployException('Deploy file is empty or not exists.');
        }

        $this->content = strval($content);
    }

    public function restore(): void
    {
        \File::put(config('gitlab-deploy.deployer-php'), $this->content);
    }
}
