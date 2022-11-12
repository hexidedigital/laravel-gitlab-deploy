<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console;

use File;
use HexideDigital\GitlabDeploy\GitlabDeployServiceProvider;
use Illuminate\Console\Command;

class GitlabDeployInstallCommand extends Command
{
    protected $hidden = true;

    protected $name = 'gitlab-deploy:install';
    protected $description = 'Install the package';

    protected string $packageName = 'gitlab-deploy';

    public function handle()
    {
        $this->info('Installing package...');

        $this->info('Publishing configuration...');

        $this->configFile();

        $this->info('Publishing sample files...');

        $this->sampleFiles();

        $this->info('Installed');
    }

    protected function configExists($fileName): bool
    {
        return File::exists(config_path($fileName));
    }

    protected function shouldOverwriteConfig(): bool
    {
        return $this->confirm(
            'Config file already exists. Do you want to overwrite it?',
            false
        );
    }

    protected function publishConfiguration($forcePublish = false): void
    {
        $params = [
            '--provider' => GitlabDeployServiceProvider::class,
            '--tag' => "{$this->packageName}-config",
        ];

        if ($forcePublish === true) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    protected function sampleFiles(): void
    {
        $params = [
            '--provider' => GitlabDeployServiceProvider::class,
            '--tag' => "{$this->packageName}-examples",
            '--force' => true,
        ];

        $this->call('vendor:publish', $params);
    }

    protected function configFile(): void
    {
        if (!$this->configExists("$this->packageName.php")) {
            $this->publishConfiguration();
            $this->info('Published configuration');

            return;
        }

        if ($this->shouldOverwriteConfig()) {
            $this->info('Overwriting configuration file...');
            $this->publishConfiguration(true);

            return;
        }

        $this->info('Existing configuration was not overwritten');
    }
}
