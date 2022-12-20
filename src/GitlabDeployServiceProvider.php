<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\Console\Commands\GitlabDeployInstallCommand;
use HexideDigital\GitlabDeploy\Console\Commands\PrepareDeployCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GitlabDeployServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('gitlab-deploy')
            ->hasConfigFile('gitlab-deploy')
            ->hasViews('gitlab-deploy')
            ->hasCommands([
                GitlabDeployInstallCommand::class,
                PrepareDeployCommand::class,
            ])
            ->publishesServiceProvider('GitlabDeployServiceProvider');
    }

    public function packageBooted(): void
    {
        $this->publishes([
            $this->package->basePath('/../examples/deploy.php.stub') => base_path('deploy.php'),
            $this->package->basePath('/../examples/deploy-prepare.example.yml') => base_path('.deploy/deploy-prepare.yml'),
            $this->package->basePath('/../examples/.gitignore.stub') => base_path('.deploy/.gitignore'),
        ], "{$this->package->shortName()}-examples");
    }
}
