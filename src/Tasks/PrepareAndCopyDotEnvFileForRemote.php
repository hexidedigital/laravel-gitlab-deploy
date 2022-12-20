<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;

final class PrepareAndCopyDotEnvFileForRemote extends BaseTask implements Task
{
    protected string $name = '🌳 Setup env file for remote server and move to server';

    public function execute(Pipedata $pipeData): void
    {
        $envMain = $this->getReplacements()->replace('{{PROJ_DIR}}/.env');
        $envBackup = $this->getReplacements()->replace('{{PROJ_DIR}}/.env.backup');

        $this->storeOriginalFiles($envMain, $envBackup);

        $this->fillEnvFile($envMain);

        $this->copyFileToRemote($envMain);

        $this->getExecutor()->runCommand("cp $envMain {{PROJ_DIR}}/.env.host");

        $this->restoreFiles($envBackup, $envMain);

        $this->removeFile($envBackup);
    }

    /**
     * @return array<string, string>
     */
    private function getEnvReplaces(): array
    {
        $mail = $this->getState()->getStage()->hasMailOptions()
            ? [
                '^MAIL_HOST=.*$' => $this->getReplacements()->replace('MAIL_HOST={{MAIL_HOSTNAME}}'),
                '^MAIL_PORT=.*$' => 'MAIL_PORT=587',
                '^MAIL_USERNAME=.*$' => $this->getReplacements()->replace('MAIL_USERNAME={{MAIL_USER}}'),
                '^MAIL_PASSWORD=.*$' => $this->getReplacements()->replace('MAIL_PASSWORD={{MAIL_PASSWORD}}'),
                '^MAIL_ENCRYPTION=.*$' => 'MAIL_ENCRYPTION=tls',
                '^MAIL_FROM_ADDRESS=.*$' => $this->getReplacements()->replace('MAIL_FROM_ADDRESS={{MAIL_USER}}'),
            ]
            : [];

        /*fixme - generating app key*/
        if (!$this->isPrintOnly()) {
            $this->getCommand()->call('key:generate');
        }

        return array_merge($mail, [
            '^APP_URL=.*$' => $this->getReplacements()->replace('APP_URL={{DEPLOY_DOMAIN}}'),

            '^DB_DATABASE=.*$' => $this->getReplacements()->replace('DB_DATABASE={{DB_DATABASE}}'),
            '^DB_USERNAME=.*$' => $this->getReplacements()->replace('DB_USERNAME={{DB_USERNAME}}'),
            '^DB_PASSWORD=.*$' => $this->getReplacements()->replace('DB_PASSWORD={{DB_PASSWORD}}'),
        ]);
    }

    /**
     * @param string $envMain
     * @return void
     */
    private function copyFileToRemote(string $envMain): void
    {
        if (!$this->confirmAction('Copy env file to remote server?', true)) {
            $this->skipping('Coping file to remote server');

            return;
        }

        $this->getLogger()->appendEchoLine('<span class="mt-1">Coping file to remote</span>', 'comment');

        $this->canAskPassword();

        $sharedDir = '{{DEPLOY_BASE_DIR}}/shared';
        $this->getExecutor()->runCommand(
            "ssh {{remoteSshCredentials}} 'test -d $sharedDir || mkdir -p $sharedDir'"
        );
        $this->getExecutor()->runCommand(
            "scp {{remoteScpOptions}} \"$envMain\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"$sharedDir/\"",
            function ($type, $buffer) {
                $this->getLogger()->appendEchoLine($type . ' > ' . trim($buffer));
            }
        );
    }

    /**
     * @param string $envBackup
     * @param string $envMain
     * @return void
     */
    private function restoreFiles(string $envBackup, string $envMain): void
    {
        $this->getLogger()->appendEchoLine('<span class="mt-1">Restore original env file</span>', 'comment');

        $this->getExecutor()->runCommand("cp $envBackup $envMain");
    }

    /**
     * @param string $envMain
     * @param string $envBackup
     * @return void
     */
    private function storeOriginalFiles(string $envMain, string $envBackup): void
    {
        $this->getLogger()->appendEchoLine(
            '<span class="mt-1">Backup original env file and create for host</span>',
            'comment'
        );

        $this->getExecutor()->runCommand("cp $envMain $envBackup");
    }

    /**
     * @param string $envMain
     * @return void
     */
    private function fillEnvFile(string $envMain): void
    {
        $this->getLogger()->appendEchoLine('<span class="mt-1">Filling env file for host...</span>', 'comment');

        $this->getExecutor()->runCommand("cp {{PROJ_DIR}}/.env.example $envMain");

        $envReplaces = $this->getEnvReplaces();

        $this->getLogger()->appendEchoLine(
            view('gitlab-deploy::console.code-fragment', ['content' => var_export($envReplaces, true)])->render()
        );

        $this->writeContentWithReplaces($envMain, $envReplaces);
    }
}
