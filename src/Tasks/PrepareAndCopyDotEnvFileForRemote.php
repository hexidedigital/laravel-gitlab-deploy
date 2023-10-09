<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Console\KeyGenerateCommand;

final class PrepareAndCopyDotEnvFileForRemote extends BaseTask implements Task
{
    protected string $name = '🌳 Setup env file for remote server and move to server';

    private string $envMainPath;
    private string $localEnvBackup;

    public function execute(Pipedata $pipeData): void
    {
        $this->envMainPath = $this->getReplacements()->replace('{{PROJ_DIR}}/.env');
        $this->localEnvBackup = config('gitlab-deploy.working-dir') . '/' . date('YmdHis') . '.env';

        $this->makeBackupForLocalEnvFile();

        try {
            $this->fillVariablesToFileForHost();

            $this->copyFileToRemoteHost();

            $this->saveGeneratedHostFile();
        } finally {
            $this->restoreLocalEnvFile();
        }
    }

    private function makeBackupForLocalEnvFile(): void
    {
        $this->getLogger()->line(
            '<span class="mt-1">Backup original env file</span>',
            'comment'
        );

        $this->copyFile($this->envMainPath, $this->localEnvBackup);
        $this->getLogger()->line("cp $this->envMainPath $this->localEnvBackup");
    }

    private function fillVariablesToFileForHost(): void
    {
        $this->getLogger()->line('<span class="mt-1">Filling env file for host...</span>', 'comment');

        // make clean copy from example file
        $envExample = $this->getReplacements()->replace('{{PROJ_DIR}}/.env.example');
        $this->copyFile($envExample, $this->envMainPath);
        $this->getLogger()->line("cp $envExample $this->envMainPath");

        $envReplaces = $this->getEnvReplaces();

        $this->getLogger()->line(
            view('gitlab-deploy::console.code-fragment', ['content' => var_export($envReplaces, true)])->render()
        );

        $this->writeContentWithReplaces($this->envMainPath, $envReplaces);
    }

    private function copyFileToRemoteHost(): void
    {
        if (!$this->confirmAction('Copy env file to remote server?', true)) {
            $this->skipping('Coping file to remote server');

            return;
        }

        $this->getLogger()->line('<span class="mt-1">Coping file to remote</span>', 'comment');

        $this->canAskPassword();

        $sharedDir = '{{DEPLOY_BASE_DIR}}/shared';
        $this->getExecutor()->runCommand(
            "ssh {{remoteSshCredentials}} 'test -d $sharedDir || mkdir -p $sharedDir'"
        );
        $this->getExecutor()->runCommand(
            "scp {{remoteScpOptions}} \"$this->envMainPath\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"$sharedDir/\"",
            function ($type, $buffer) {
                $this->getLogger()->line($type . ' > ' . trim($buffer));
            }
        );
    }

    private function saveGeneratedHostFile(): void
    {
        $envHost = config('gitlab-deploy.working-dir') . "/env.host";

        $this->copyFile($this->envMainPath, $envHost);
        $this->getLogger()->line("cp $this->envMainPath $envHost");
    }

    private function restoreLocalEnvFile(): void
    {
        $this->getLogger()->line('<span class="mt-1">Restore original env file</span>', 'comment');

        $this->copyFile($this->localEnvBackup, $this->envMainPath);
        $this->getLogger()->line("cp $this->localEnvBackup $this->envMainPath");
    }

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

        return array_merge($mail, [
            '^APP_KEY=.*$' => 'APP_KEY=' . $this->generateRandomKey(),
            '^APP_URL=.*$' => $this->getReplacements()->replace('APP_URL={{DEPLOY_DOMAIN}}'),

            '^DB_DATABASE=.*$' => $this->getReplacements()->replace('DB_DATABASE={{DB_DATABASE}}'),
            '^DB_USERNAME=.*$' => $this->getReplacements()->replace('DB_USERNAME={{DB_USERNAME}}'),
            '^DB_PASSWORD=.*$' => $this->getReplacements()->replace('DB_PASSWORD={{DB_PASSWORD}}'),
        ]);
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     * @see KeyGenerateCommand::generateRandomKey()
     */
    private function generateRandomKey(): string
    {
        return 'base64:' . base64_encode(Encrypter::generateKey(config('app.cipher')));
    }
}
