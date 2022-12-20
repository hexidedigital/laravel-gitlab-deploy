<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console\Commands;

use File;
use HexideDigital\GitlabDeploy\GitlabDeployServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GitlabDeployInstallCommand extends Command
{
    protected $hidden = true;

    protected $name = 'gitlab-deploy:install';
    protected $description = 'Install the package';

    protected string $packageName = 'gitlab-deploy';

    public function handle(): void
    {
        $this->info('Installing package.');

        $this->publishConfigFile();

        $this->sampleFiles();

        $this->updateGitignoreFile();

        $this->info('Installed.');
    }

    protected function configExists(string $fileName): bool
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

    protected function publishConfiguration(bool $forcePublish = false): void
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
        $this->info('Publishing sample files...');

        $params = [
            '--provider' => GitlabDeployServiceProvider::class,
            '--tag' => "{$this->packageName}-examples",
            '--force' => true,
        ];

        $this->call('vendor:publish', $params);
    }

    protected function publishConfigFile(): void
    {
        $this->info('Publishing configuration.');

        if (!$this->configExists("$this->packageName.php")) {
            $this->publishConfiguration();
            $this->info('Published configuration.');

            return;
        }

        if ($this->shouldOverwriteConfig()) {
            $this->info('Overwriting configuration file.');
            $this->publishConfiguration(true);

            return;
        }

        $this->info('Existing configuration was not overwritten.');
    }

    protected function updateGitignoreFile(): void
    {
        $gitignoreFile = base_path('.gitignore');
        if (!File::isFile($gitignoreFile)) {
            return;
        }

        $this->info('Updating: .gitignore file');

        $content = File::get($gitignoreFile);

        $this->appendIgnore($content, $this->getSshDir());
        $this->appendIgnore($content, $this->getWorkingDir());

        if ($content === File::get($gitignoreFile)) {
            $this->info('No need to update.');
        } else {
            File::put($gitignoreFile, $content);

            $this->info('Updated: .gitignore file');
        }
    }

    protected function appendIgnore(string $content, string $pattern): string
    {
        if (Str::contains($content, $pattern)) {
            return $content;
        }

        return $content . PHP_EOL . $pattern;
    }

    protected function getSshDir(): string
    {
        return Str::of(config('gitlab-deploy.ssh.folder'))
            ->replace('{{STAGE}}', '')
            ->replace(base_path(), '')
            ->start('/')
            ->value();
    }

    protected function getWorkingDir(): string
    {
        return Str::of(config('gitlab-deploy.config-file'))
            ->replace(base_path(), '')
            ->value();
    }
}
