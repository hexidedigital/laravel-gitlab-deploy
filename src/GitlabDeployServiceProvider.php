<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\Console\PrepareDeployCommand;
use HexideDigital\GitlabDeploy\Contracts\ParsesConfiguration;
use HexideDigital\GitlabDeploy\DeployOptions\ParseConfiguration;
use Illuminate\Support\ServiceProvider;

class GitlabDeployServiceProvider extends ServiceProvider
{
    protected array $commands = [
        'deploy-gitlab' => PrepareDeployCommand::class,
    ];

    public array $bindings = [
        ParsesConfiguration::class => ParseConfiguration::class,
    ];

    public function register()
    {
        foreach ($this->commands as $alias => $command) {
            $this->app->singleton($alias, $command);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            $this->packagePath('examples/deploy.php.stub') => $this->app->basePath('.deploy.php'),
            $this->packagePath('examples/deploy-prepare.example.yml') => $this->app->basePath('.deploy/deploy-prepare.yml'),
            $this->packagePath('examples/.gitignore.stub') => $this->app->basePath('.deploy/.gitignore'),
        ], 'gitlab-deploy');

        $this->publishes([
            $this->packagePath('config/gitlab-deploy.php') => $this->app->configPath('gitlab-deploy.php'),
        ], 'config');

        $this->mergeConfigFrom($this->packagePath('config/gitlab-deploy.php'), 'gitlab-deploy');

        if ($this->app->runningInConsole()) {
            $this->commands(array_keys($this->commands));
        }
    }

    /**
     * Get the absolute path to some package resource.
     *
     * @param string $path The relative path to the resource
     *
     * @return string
     */
    protected function packagePath(string $path): string
    {
        return __DIR__.'/../'.$path;
    }
}
