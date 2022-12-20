<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class InsertCustomAliasesOnRemoteHost extends BaseTask implements Task
{
    protected string $name = '💻 Append custom aliases to remote server';

    public function execute(Pipedata $pipeData): void
    {
        $shouldPutAliases = $this->confirmAction('Are you want to add aliases for laravel artisan command?', false);

        $filePath = __DIR__ . '/../../examples/.bash_aliases';

        if (!$shouldPutAliases || $this->isPrintOnly()) {
            $this->skipping();

            $content = $this->getContent($filePath);

            $bashAliases = $this->getReplacements()->replace($content);

            $this->getLogger()->getFileLogger()->line($bashAliases);
            $this->getLogger()->getConsoleLogger()->line('Bash aliases written to log file');

            return;
        }

        $aliasesPath = $this->getReplacements()->replace(storage_path('deployer/.bash_aliases-{{STAGE}}'));
        $aliasesLoader = <<<SHELL
if [ -f  ~/.bash_aliases ];
    then . ~/.bash_aliases
fi
SHELL;

        $this->getExecutor()->runCommand("cp $filePath $aliasesPath");
        $this->getLogger()->line(
            'Optionally, copy next script to load aliases into <span class="text-info">`~/.bashrc`</span> file.'
        );
        $this->getLogger()->line(
            view('gitlab-deploy::console.code-fragment', ['content' => $aliasesLoader])->render()
        );

        $this->canAskPassword();

        $this->getExecutor()->runCommand(
            "scp {{remoteScpOptions}} \"$aliasesPath\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"~/.bash_aliases\"",
            function ($type, $buffer) {
                $this->getLogger()->line($type . ' > ' . trim($buffer));
            }
        );
    }
}
