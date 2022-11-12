<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\Console\PrepareDeployCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GitlabDeployServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('gitlab-deploy')
            ->hasConfigFile('gitlab-deploy')
            ->hasCommands([
                PrepareDeployCommand::class,
            ])
            ->publishesServiceProvider('GitlabDeployServiceProvider')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, and welcome to my great new package!');
                    })
                    ->askToStarRepoOnGitHub('hexidedigital/laravel-gitlab-deploy')
                    ->publishConfigFile()
                    ->endWith(function (InstallCommand $command) {
                        $command->info('Have a great day!');
                    });
            });
    }

    public function packageBooted(): void
    {
        $this->publishes([
            $this->package->basePath('/../examples/deploy.php.stub') => base_path('deploy.php'),
            $this->package->basePath('/../examples/deploy-prepare.example.yml') => base_path('.deploy/deploy-prepare.yml'),
            $this->package->basePath('/../examples/.gitignore.stub') => base_path('.deploy/.gitignore'),
        ], $this->package->shortName());
    }
}
