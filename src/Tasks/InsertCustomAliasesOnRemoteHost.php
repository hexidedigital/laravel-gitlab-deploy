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

        if (!$shouldPutAliases || $this->isPrintOnly()) {
            $this->skipping();

            $this->getLogger()->getFileLogger()->line($this->getContentForBashAliases());
            $this->getLogger()->getConsoleLogger()->line('Bash aliases written to log file');

            return;
        }

        $deployAliasesPath = $this->getReplacements()->replace(
            config('gitlab-deploy.working-dir') . '/{{STAGE}}.bash_aliases'
        );

        $this->getExecutor()->runCommand("cp {$this->getPathToAliases()} {$deployAliasesPath}");
        $this->writeContent($deployAliasesPath, $this->getContentForBashAliases());

        $this->getLogger()->line(
            'Optionally, copy next script to load aliases into <span class="text-info">`~/.bashrc`</span> file.'
        );
        $this->getLogger()->line(
            view('gitlab-deploy::console.code-fragment', ['content' => $this->getAliasesLoader()])->render()
        );

        $this->canAskPassword();

        $this->getExecutor()->runCommand(
            "scp {{remoteScpOptions}} \"$deployAliasesPath\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"~/.bash_aliases\"",
            function ($type, $buffer) {
                $this->getLogger()->line($type . ' > ' . trim($buffer));
            }
        );
    }

    private function getPathToAliases(): string
    {
        return __DIR__ . '/../../examples/.bash_aliases';
    }

    private function getAliasesLoader(): string
    {
        return <<<SHELL
if [ -f  ~/.bash_aliases ];
    then . ~/.bash_aliases
fi
SHELL;
    }

    private function getContentForBashAliases(): string
    {
        $originalContent = '';
        $this->getExecutor()->runCommand(
            'ssh {{remoteSshCredentials}} "if [ -f ~/.bash_aliases ]; then cat ~/.bash_aliases; fi"',
            function ($type, $buffer) use (&$originalContent) {
                $originalContent = $buffer;
            }
        );

        $newContent = $this->getReplacements()->replace($this->getContent($this->getPathToAliases()));

        return <<<DOC
        $originalContent
        $newContent
        DOC;
    }
}
