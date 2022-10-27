<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

final class PrepareAndCopyDotEnvFileForRemote extends BaseTask implements Task
{
    protected string $name = 'setup env file for remote server and move to server';

    public function __construct(
        private readonly Filesystem $filesystem,
    )
    {
    }

    public function execute(): void
    {
        $envExample = $this->replacements->replace('{{PROJ_DIR}}/.env.example');
        $envOriginal = $this->replacements->replace('{{PROJ_DIR}}/.env');
        $envBackup = $this->replacements->replace('{{PROJ_DIR}}/.env.backup');
        $envHost = $envOriginal;

        $this->moveFiles($envOriginal, $envBackup, $envExample, $envHost);

        $envReplaces = $this->getEnvReplaces();

        $this->logger->appendEchoLine('Filling env file for host', 'comment');
        $this->logger->appendEchoLine(var_export($envReplaces, true));

        $this->updateWithReplaces($this->filesystem, $envHost, $envReplaces);

        $this->copyFileToRemote($envHost);

        $this->restoreFiles($envHost, $envBackup, $envOriginal);
    }

    /**
     * @return array<string, string>
     */
    public function getEnvReplaces(): array
    {
        $mail = $this->state->getStage()->hasMailOptions()
            ? [
                'MAIL_HOST=mailhog' => $this->replacements->replace('MAIL_HOST={{MAIL_HOSTNAME}}'),
                'MAIL_PORT=1025' => 'MAIL_PORT=587',
                'MAIL_USERNAME=null' => $this->replacements->replace('MAIL_USERNAME={{MAIL_USER}}'),
                'MAIL_PASSWORD=null' => $this->replacements->replace('MAIL_PASSWORD={{MAIL_PASSWORD}}'),
                'MAIL_ENCRYPTION=null' => 'MAIL_ENCRYPTION=tls',
                'MAIL_FROM_ADDRESS=null' => $this->replacements->replace('MAIL_FROM_ADDRESS={{MAIL_USER}}'),
            ]
            : [];

        $output = new BufferedOutput();
        Artisan::call('key:generate', ['--show' => true], $output);
        $appKey = trim($output->fetch());

        return array_merge($mail, [
            'APP_KEY=' => 'APP_KEY='.$appKey,
            'APP_URL=' => $this->replacements->replace('APP_URL="{{DEPLOY_DOMAIN}}"#'),

            'DB_DATABASE=' => $this->replacements->replace('DB_DATABASE="{{DB_DATABASE}}"#'),
            'DB_USERNAME=' => $this->replacements->replace('DB_USERNAME="{{DB_USERNAME}}"#'),
            'DB_PASSWORD=' => $this->replacements->replace('DB_PASSWORD="{{DB_PASSWORD}}"#'),
        ]);
    }

    /**
     * @param array|string $envHost
     * @return void
     */
    public function copyFileToRemote(array|string $envHost): void
    {
        if (!$this->confirmAction('Copy env file to remote server?', true)) {
            return;
        }

        $this->logger->appendEchoLine('Coping to remote', 'comment');

        $this->logger->appendEchoLine($this->replacements->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>'));

        $sharedDir = '{{DEPLOY_BASE_DIR}}/shared';
        $this->executor->runCommand(
            "ssh {{remoteSshCredentials}} 'test -d $sharedDir || mkdir -p $sharedDir'"
        );
        $this->executor->runCommand(
            "scp {{remoteScpOptions}} \"$envHost\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"$sharedDir/\"",
            function ($type, $buffer) {
                $this->logger->appendEchoLine($type.' > '.trim($buffer));
            }
        );
    }

    /**
     * @param array|string $envHost
     * @param array|string $envBackup
     * @param array|string $envOriginal
     * @return void
     */
    public function restoreFiles(array|string $envHost, array|string $envBackup, array|string $envOriginal): void
    {
        $this->logger->appendEchoLine('Restore original env file', 'comment');
        $this->executor->runCommand("cp $envHost $envHost.host");
        $this->executor->runCommand("cp $envBackup $envOriginal");
    }

    /**
     * @param array|string $envOriginal
     * @param array|string $envBackup
     * @param array|string $envExample
     * @param array|string $envHost
     * @return void
     */
    public function moveFiles(array|string $envOriginal, array|string $envBackup, array|string $envExample, array|string $envHost): void
    {
        $this->logger->appendEchoLine('Backup original env file and create for host', 'comment');

        $this->executor->runCommand("cp $envOriginal $envBackup");
        $this->executor->runCommand("cp $envExample $envHost");
    }
}
