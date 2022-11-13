<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use Illuminate\Contracts\Filesystem\Filesystem;

final class InsertCustomAliasesOnRemoteHost extends BaseTask implements Task
{
    protected string $name = 'append custom aliases';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function handle(): void
    {
        $shouldPutAliases = $this->confirmAction('Are you want to add aliases for laravel artisan command?', false);

        $filePath = __DIR__.'/../../examples/.bash_aliases';

        if (!$shouldPutAliases) {
            $bashAliases = $this->replacements->replace($this->filesystem->get($filePath));

            $this->logger->writeToFile($bashAliases);

            return;
        }

        $aliasesPath = $this->replacements->replace(storage_path('deployer/.bash_aliases-{{STAGE}}'));
        $aliasesLoader = <<<SHELL
if [ -f  ~/.bash_aliases ];
    then . ~/.bash_aliases
fi
SHELL;

        $this->executor->runCommand("cp $filePath $aliasesPath");

        $this->updateWithReplaces($this->filesystem, $aliasesPath);

        $this->logger->appendEchoLine('Optionally, copy next script to load aliases into ~/.bashrc file.', 'comment');
        $this->logger->appendEchoLine($aliasesLoader);

        $this->logger->appendEchoLine($this->replacements->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>'));
        $this->executor->runCommand(
            "scp {{remoteScpOptions}} \"$aliasesPath\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"~/.bash_aliases\"",
            function ($type, $buffer) {
                $this->logger->appendEchoLine($type.' > '.trim($buffer));
            }
        );
    }
}
