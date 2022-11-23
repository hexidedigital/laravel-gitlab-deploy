<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Contracts\Filesystem\Filesystem;

final class InsertCustomAliasesOnRemoteHost extends BaseTask implements Task
{
    protected string $name = 'append custom aliases';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function execute(Pipedata $pipeData): void
    {
        $shouldPutAliases = $this->confirmAction('Are you want to add aliases for laravel artisan command?', false);

        $filePath = __DIR__ . '/../../examples/.bash_aliases';

        if (!$shouldPutAliases) {
            $bashAliases = $this->getReplacements()->replace($this->filesystem->get($filePath));

            $this->getLogger()->writeToFile($bashAliases);

            return;
        }

        $aliasesPath = $this->getReplacements()->replace(storage_path('deployer/.bash_aliases-{{STAGE}}'));
        $aliasesLoader = <<<SHELL
if [ -f  ~/.bash_aliases ];
    then . ~/.bash_aliases
fi
SHELL;

        $this->getExecutor()->runCommand("cp $filePath $aliasesPath");

        $this->updateWithReplaces($this->filesystem, $aliasesPath);

        $this->getLogger()->appendEchoLine('Optionally, copy next script to load aliases into ~/.bashrc file.', 'comment');
        $this->getLogger()->appendEchoLine($aliasesLoader);

        $this->getLogger()->appendEchoLine(
            $this->getReplacements()->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>')
        );
        $this->getExecutor()->runCommand(
            "scp {{remoteScpOptions}} \"$aliasesPath\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"~/.bash_aliases\"",
            function ($type, $buffer) {
                $this->getLogger()->appendEchoLine($type . ' > ' . trim($buffer));
            }
        );
    }
}
