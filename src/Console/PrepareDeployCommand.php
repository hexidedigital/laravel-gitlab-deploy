<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console;

use ErrorException;
use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\ParseConfiguration;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\Tasks\GitlabVariablesCreator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

class PrepareDeployCommand extends Command
{
    // ---------------------
    // only to describe command
    // ---------------------
    protected $name = 'deploy:gitlab';
    protected $description = 'Command to prepare your deploy';


    // ---------------------
    // same static or constants, no editable
    // ---------------------
    // in future can be moved to config file
    protected static string $gitlabServer = 'gitlab.hexide-digital.com,188.34.141.230';
    protected static string $deployYamlFile = '.deploy/deploy-prepare.yml';


    // ---------------------
    // static patterns for replaces
    // ---------------------
    // replaces after step 2
    protected static string $deployPhpFile = '{{PROJ_DIR}}/deploy.php';
    protected static string $sshDirPath = '{{PROJ_DIR}}/.ssh/{{CI_COMMIT_REF_NAME}}';
    // replaces after step 3
    protected static string $remoteSshCredentials = '-i "{{IDENTITY_FILE}}" -p {{SSH_PORT}} "{{DEPLOY_USER}}@{{DEPLOY_SERVER}}"';
    protected static string $remoteScpOptions = '-i "{{IDENTITY_FILE}}" -P {{SSH_PORT}}';


    // ---------------------
    // editable across executing
    // ---------------------
    protected int $step = 1;

    // ---------------------
    // runtime defined properties
    // ---------------------
    protected BasicLogger $logger;
    protected Replacements $replacements;
    protected Configurations $configurations;
    protected Stage $stage;
    protected VariableBag $gitlabVariablesBag;

    protected string $deployInitialContent;

    // --------------- command info --------------

    protected function getArguments(): array
    {
        return [
            new InputArgument('stage', InputArgument::REQUIRED, 'Deploy stage'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            new InputOption('force', 'f', InputOption::VALUE_NONE, 'Confirm all choices and force all commands'),
            new InputOption(
                'aliases',
                null,
                InputOption::VALUE_NONE,
                'Append custom aliases for artisan and php to ~/.bashrc'
            ),
            new InputOption(
                'only-print',
                null,
                InputOption::VALUE_NONE,
                'Only print commands, with-out executing commands'
            ),
        ];
    }

    public function handle(): int
    {
        $this->createLogFile();

        $finishedWithError = false;

        try {
            // prepare
            $this->parseConfigurations();

            $this->setupReplacements();
            $this->setupGitlabVariables();

            $this->deployInitialContent = $this->task_saveInitialContentOfDeployFile();

            // begin of process
            $this->task_generateSshKeysOnLocalhost();
            $this->task_copySshKeysOnRemoteHost();
            $this->task_generateSshKeysOnRemoteHost();

            $this->task_createProjectVariablesOnGitlab();
            $this->task_addGitlabToKnownHostsOnRemoteHost();

            // $this->task_runDeployPrepareCommand();

            $this->task_putNewVariablesToDeployFile();
            $this->task_prepareAndCopyDotEnvFileForRemote();
            $this->task_runFirstDeployCommand();
            $this->task_rollbackDeployFileContent();

            $this->task_insertCustomAliasesOnRemoteHost();
            $this->task_ideaSetup();
        } catch (GitlabDeployException $exception) {
            $finishedWithError = true;
            $this->printError('Deploy command unexpected finished.', $exception);
        } catch (\Exception $exception) {
            $finishedWithError = true;
            $this->printError('Error happened! See laravel log file.', $exception);
        } finally {
            $this->logger->closeFile();
            $this->newLine();
        }

        if ($finishedWithError) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function printError(string $error, \Exception $exception): void
    {
        $this->writeLogLine($error, 'error');
        $this->writeLogLine($exception->getMessage(), 'error');
    }

    private function createLogFile()
    {
        $this->logger = new BasicLogger();
        $this->logger->openFile();
    }

    /**
     * @throws GitlabDeployException
     */
    private function parseConfigurations(): void
    {
        $parser = app(ParseConfiguration::class);

        $parser->parseFile(base_path(static::$deployYamlFile));

        $this->configurations = $parser->configurations;
        $this->stage = $parser->configurations->stageBag->get($this->getStageName());
    }

    private function setupReplacements(): void
    {
        /*-----------------------
         * step 1
         *
         * server - USER HOST SSH_PORT DEPLOY_DOMAIN DEPLOY_SERVER DEPLOY_USER DEPLOY_PASS
         */
        $this->replacements = new Replacements(
            $this->stage->server->toArray()
        );

        /*-----------------------
         * step 2
         *
         * options - CI_REPOSITORY_URL DEPLOY_BASE_DIR BIN_PHP BIN_COMPOSER
         * database - DB_DATABASE DB_USERNAME DB_PASSWORD
         * mail - MAIL_HOSTNAME MAIL_USER MAIL_PASSWORD
         *
         * other - PROJ_DIR CI_COMMIT_REF_NAME
         */
        $this->replacements->mergeReplaces(
            array_merge(
                $this->stage->options->toArray(),
                $this->stage->database->toArray(),
                $this->stage->hasMailOptions() ? $this->stage->mail->toArray() : [],
                [
                    '{{PROJ_DIR}}' => base_path(),
                    '{{CI_COMMIT_REF_NAME}}' => $this->getStageName(),

                    '{{DEPLOY_BASE_DIR}}' => $this->replace($this->stage->options->baseDir),
                ],
            )
        );

        static::$deployPhpFile = $this->replace(static::$deployPhpFile);
        static::$sshDirPath = $this->replace(static::$sshDirPath);

        /*-----------------------
         * step 3
         */
        $this->replacements->mergeReplaces([
            '{{IDENTITY_FILE}}' => static::$sshDirPath.'/id_rsa',
            '{{IDENTITY_FILE_PUB}}' => static::$sshDirPath.'/id_rsa.pub',

            '{{DEPLOY_PHP_ENV}}' => $this->replace(
                <<<PHP
\$CI_REPOSITORY_URL = "{{CI_REPOSITORY_URL}}";
\$CI_COMMIT_REF_NAME = "{{CI_COMMIT_REF_NAME}}";
\$BIN_PHP = "{{BIN_PHP}}";
\$BIN_COMPOSER = "{{BIN_COMPOSER}}";
\$DEPLOY_BASE_DIR = "{{DEPLOY_BASE_DIR}}";
\$DEPLOY_SERVER = "{{DEPLOY_SERVER}}";
\$DEPLOY_USER = "{{DEPLOY_USER}}";
\$SSH_PORT = "{{SSH_PORT}}";
PHP
            )
            ,
        ]);

        static::$remoteSshCredentials = $this->replace(static::$remoteSshCredentials);
    }

    private function setupGitlabVariables(): void
    {
        $bag = new VariableBag();

        $variables = [
            'BIN_PHP' => $this->replace('{{BIN_PHP}}'),
            'BIN_COMPOSER' => $this->replace('{{BIN_COMPOSER}}'),

            'DEPLOY_BASE_DIR' => $this->replace('{{DEPLOY_BASE_DIR}}'),
            'DEPLOY_SERVER' => $this->replace('{{DEPLOY_SERVER}}'),
            'DEPLOY_USER' => $this->replace('{{DEPLOY_USER}}'),
            'SSH_PORT' => $this->replace('{{SSH_PORT}}'),

            'SSH_PRIVATE_KEY' => '-----BEGIN OPENSSH PRIVATE ',
            'SSH_PUB_KEY' => 'rsa-ssh AAA....AAA user@host',

            'CI_ENABLED' => '0',
        ];

        $scope = $this->getStageName();

        foreach ($variables as $key => $value) {
            $variable = new Variable(
                key: $key,
                scope: $scope,
                value: $value
            );

            $bag->add($variable->key, $variable);
        }

        $this->gitlabVariablesBag = $bag;
    }

    /**
     * @throws GitlabDeployException
     */
    private function task_saveInitialContentOfDeployFile(): string
    {
        $initialContent = $this->getContent(static::$deployPhpFile);

        if (empty($initialContent)) {
            throw new GitlabDeployException('Deploy file is empty or not exists.');
        }

        return $initialContent;
    }

    private function task_generateSshKeysOnLocalhost(): void
    {
        $this->newSection('generate ssh keys - private key to gitlab (localhost)');

        $this->forceExecuteCommand('mkdir -p '.static::$sshDirPath);

        if (!$this->isSshFilesExits() || $this->confirmAction('Should generate and override existed key?')) {
            $option = $this->isSshFilesExits() ? '-y' : '';
            $this->optionallyExecuteCommand('ssh-keygen -t rsa -f "{{IDENTITY_FILE}}" -N "" '.$option);
        }

        $this->writeLogLine('cat {{IDENTITY_FILE}}', 'info');
        $this->gitlabVariablesBag['SSH_PRIVATE_KEY'] = $this->getContent($this->replace('{{IDENTITY_FILE}}'));
    }

    private function isSshFilesExits(): bool
    {
        return file_exists($this->replace('{{IDENTITY_FILE}}'))
            || file_exists($this->replace('{{IDENTITY_FILE_PUB}}'));
    }

    private function task_copySshKeysOnRemoteHost(): void
    {
        $this->newSection('copy ssh to server - public key to remote host');
        $this->writeLogLine($this->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>'));

        $this->optionallyExecuteCommand('ssh-copy-id '.static::$remoteSshCredentials);
    }

    private function task_generateSshKeysOnRemoteHost(): void
    {
        $this->newSection('Generate generate ssh-keys on remote host');

        $sshRemote = 'ssh '.static::$remoteSshCredentials;

        if ($this->confirmAction('Generate ssh keys on remote host')) {
            $this->optionallyExecuteCommand($sshRemote.' "ssh-keygen -t rsa -f ~/.ssh/id_rsa -N \"\""');
        }

        $this->optionallyExecuteCommand($sshRemote.' "cat ~/.ssh/id_rsa.pub"', function ($type, $buffer) {
            $this->gitlabVariablesBag['SSH_PUB_KEY'] = $buffer;
        });

        $this->writeLogLine('Remote pub-key: '.$this->gitlabVariablesBag['SSH_PUB_KEY'], 'info');
    }

    private function task_createProjectVariablesOnGitlab(): void
    {
        $this->newSection('gitlab variables');

        // print to file on case if error happens
        $rows = [];
        foreach ($this->gitlabVariablesBag->except($this->gitlabVariablesBag->printAloneKeys()) as $variable) {
            $this->logger->writeToFile($variable->key.PHP_EOL.$variable->value.PHP_EOL);

            $rows[] = [$variable->key, $variable->value];
        }

        foreach ($this->gitlabVariablesBag->only($this->gitlabVariablesBag->printAloneKeys()) as $variable) {
            $this->writeLogLine($variable->key, 'comment');
            $this->writeLogLine($variable->value);
        }

        $this->table(['key', 'value'], $rows);

        $this->writeLogLine(
            "tip: put SSH_PUB_KEY => Gitlab.project -> Settings -> Repository -> Deploy keys",
            'comment'
        );

        if ($this->isOnlyPrint() || !$this->confirmAction('Update gitlab variables?')) {
            return;
        }

        $this->writeLogLine('Connecting to gitlab and creating variables...');

        $creator = app(GitlabVariablesCreator::class)
            ->setProject($this->configurations->project)
            ->setVariableBag($this->gitlabVariablesBag);

        $creator->execute();

        foreach ($creator->getMessages() as $message) {
            $this->writeLogLine($message, 'comment');
        }

        $fails = $creator->getFailMassages();

        $this->writeLogLine('Gitlab variables created with "'.sizeof($fails).'" fail messages');

        foreach ($fails as $fail) {
            $this->writeLogLine($fail, 'error');
        }
    }

    private function task_addGitlabToKnownHostsOnRemoteHost(): void
    {
        $this->newSection('add gitlab to confirmed (known hosts) on remote host');

        if (!$this->confirmAction('Append gitlab IP to remote host known_hosts file?')) {
            return;
        }

        $knownHost = '';
        $this->optionallyExecuteCommand(
            'ssh-keyscan -t ecdsa-sha2-nistp256 '.static::$gitlabServer,
            function ($type, $buffer) use (&$knownHost) {
                $knownHost = trim($buffer);
            }
        );

        $sshRemote = 'ssh '.static::$remoteSshCredentials;

        $remoteKnownHosts = '';
        $this->optionallyExecuteCommand(
            $sshRemote.' "cat ~/.ssh/known_hosts"',
            function ($type, $buffer) use (&$remoteKnownHosts) {
                $remoteKnownHosts = $buffer;
            }
        );

        if (!Str::contains($remoteKnownHosts, $knownHost)) {
            $this->optionallyExecuteCommand($sshRemote." 'echo \"$knownHost\" >> ~/.ssh/known_hosts'");
        } else {
            $this->writeLogLine('Remote server already know gitlab host.');
        }
    }

    private function task_putNewVariablesToDeployFile(): void
    {
        $this->newSection('putting static env variables to deploy file');

        $env = $this->replace('{{DEPLOY_PHP_ENV}}');

        $this->writeLogLine($env);

        $this->putContentToFile(static::$deployPhpFile, [
            '/*CI_ENV*/' => $env,
            "~/.ssh/id_rsa" => $this->replace("{{IDENTITY_FILE}}"),
        ]);
    }

    private function task_runDeployPrepareCommand(): void
    {
        $this->newSection('run deploy prepare from localhost');

        $this->optionallyExecuteCommand(
            'php {{PROJ_DIR}}/vendor/bin/dep deploy:prepare {{CI_COMMIT_REF_NAME}} -v -o branch={{CI_COMMIT_REF_NAME}}',
            function ($type, $buffer) {
                $this->writeLogLine($type.' > '.trim($buffer));
            }
        );
    }

    private function task_prepareAndCopyDotEnvFileForRemote(): void
    {
        $this->newSection('setup env file for remote server and move to server');

        $envExample = $this->replace('{{PROJ_DIR}}/.env.example');
        $envOriginal = $this->replace('{{PROJ_DIR}}/.env');
        $envBackup = $this->replace('{{PROJ_DIR}}/.env.backup');
        $envHost = $envOriginal;

        $this->writeLogLine('Backup original env file and create for host', 'comment');
        $this->optionallyExecuteCommand("cp $envOriginal $envBackup");
        $this->optionallyExecuteCommand("cp $envExample $envHost");

        $mail = $this->stage->hasMailOptions()
            ? [
                "MAIL_HOST=mailhog" => $this->replace("MAIL_HOST={{MAIL_HOSTNAME}}"),
                "MAIL_PORT=1025" => "MAIL_PORT=587",
                "MAIL_USERNAME=null" => $this->replace("MAIL_USERNAME={{MAIL_USER}}"),
                "MAIL_PASSWORD=null" => $this->replace("MAIL_PASSWORD={{MAIL_PASSWORD}}"),
                "MAIL_ENCRYPTION=null" => "MAIL_ENCRYPTION=tls",
                "MAIL_FROM_ADDRESS=null" => $this->replace("MAIL_FROM_ADDRESS={{MAIL_USER}}"),
            ]
            : [];

        $output = new BufferedOutput();
        Artisan::call('key:generate', ['--show' => true], $output);
        $appKey = trim($output->fetch());

        $envReplaces = array_merge($mail, [
            'APP_KEY=' => 'APP_KEY='.$appKey,
            'APP_URL=' => $this->replace('APP_URL="{{DEPLOY_DOMAIN}}"#'),

            'DB_DATABASE=' => $this->replace('DB_DATABASE="{{DB_DATABASE}}"#'),
            'DB_USERNAME=' => $this->replace('DB_USERNAME="{{DB_USERNAME}}"#'),
            'DB_PASSWORD=' => $this->replace('DB_PASSWORD="{{DB_PASSWORD}}"#'),
        ]);

        $this->writeLogLine('Filling env file for host', 'comment');
        $this->writeLogLine(var_export($envReplaces, true));

        $this->putContentToFile($envHost, $envReplaces);

        $this->writeLogLine('Coping to remote', 'comment');

        if (!$this->isOnlyPrint() && $this->confirmAction('Copy env file to remote server?')) {
            $this->writeLogLine($this->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>'));
            $sharedDir = "{{DEPLOY_BASE_DIR}}/shared";
            $this->optionallyExecuteCommand(
                "ssh ".static::$remoteSshCredentials." 'test -d $sharedDir || mkdir $sharedDir'"
            );
            $this->optionallyExecuteCommand(
                "scp ".self::$remoteScpOptions." \"$envHost\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"$sharedDir/\"",
                function ($type, $buffer) {
                    $this->writeLogLine($type.' > '.trim($buffer));
                }
            );
        }

        $this->writeLogLine('Restore original env file', 'comment');
        $this->optionallyExecuteCommand("cp $envHost $envHost.host");
        $this->optionallyExecuteCommand("cp $envBackup $envOriginal");
    }

    private function task_runFirstDeployCommand(): void
    {
        $this->newSection('run deploy from local');

        $fileNotExists =
            !$this->isOnlyPrint() &&
            !$this->confirmAction('Please, check if the file was copied correctly to remote host. It is right?', true);

        if ($fileNotExists) {
            // option only print disabled
            // and file not copied
            $this->writeLogLine('The deployment command was skipped.', 'error');

            return;
        }

        $this->optionallyExecuteCommand(
            'php {{PROJ_DIR}}/vendor/bin/dep deploy',
            function ($type, $buffer) {
                $this->writeLogLine($type.' > '.trim($buffer));
            }
        );
    }

    private function task_rollbackDeployFileContent(): void
    {
        $this->writeLogLine('Rollback deploy file content', 'comment');

        file_put_contents(static::$deployPhpFile, $this->deployInitialContent);
    }

    private function task_insertCustomAliasesOnRemoteHost(): void
    {
        $this->newSection('append custom aliases');

        $shouldPutAliases = $this->option('aliases');

        $mustConfirm = !($shouldPutAliases
            || $this->option('no-interaction')
            || $this->isOnlyPrint()
            || $this->isForce());

        if ($mustConfirm) {
            $shouldPutAliases = $this->confirm('Are you want to add aliases for laravel artisan command?', false);
        }

        $filePath = __DIR__.'/../../examples/.bash_aliases';

        if (!$shouldPutAliases && $this->isOnlyPrint()) {
            $bashAliases = $this->replace(file_get_contents($filePath));

            $this->logger->writeToFile($bashAliases);

            return;
        }

        if (!$shouldPutAliases) {
            return;
        }

        $aliasesPath = $this->replace("{{PROJ_DIR}}/.deploy/.bash_aliases");

        $this->optionallyExecuteCommand("cp $filePath $aliasesPath");
        $this->putContentToFile($aliasesPath);

        if ($this->configurations->version < 0.2 && $this->confirmAction(
                'Copy script to load aliases into ~/.bashrc file?',
            )) {
            $aliasesLoader = <<<SHELL
if [ -f  ~/.bash_aliases ];
    then . ~/.bash_aliases
fi
SHELL;
            $this->optionallyExecuteCommand(
                'ssh '.static::$remoteSshCredentials." 'echo \"$aliasesLoader\" >> ~/.bashrc'"
            );
        }

        $this->writeLogLine($this->replace('can ask a password - enter <comment>{{DEPLOY_PASS}}</comment>'));
        $this->optionallyExecuteCommand(
            "scp ".self::$remoteScpOptions." \"$aliasesPath\" \"{{DEPLOY_USER}}@{{DEPLOY_SERVER}}\":\"~/.bash_aliases\"",
            function ($type, $buffer) {
                $this->writeLogLine($type.' > '.trim($buffer));
            }
        );
    }

    private function task_ideaSetup(): void
    {
        $this->newSection('IDEA Setup and helpers');

        $this->writeLogLine(
            $this->replace(
                "
    <info>- mount path</info>
    {{DEPLOY_BASE_DIR}}

    <info>- site url</info>
    {{DEPLOY_SERVER}}

    <info>- add mapping for deployment</info>
    /current

    <info>- configure crontab / schedule</info>
    crontab -e

    * * * * * cd {{DEPLOY_BASE_DIR}}/current && {{BIN_PHP}} artisan schedule:run >> /dev/null 2>&1

    <info>- connect to databases (local and remote)</info>
    port: {{SSH_PORT}}
    domain: {{DEPLOY_DOMAIN}}
    db_name: {{DB_DATABASE}}
    db_user: {{DB_USERNAME}}
    password: {{DB_PASSWORD}}"
            )
        );
    }


    // --------------- output and logging --------------

    private function newSection(string $name): void
    {
        $string = strip_tags($this->step++.'. '.Str::ucfirst($name));

        $length = Str::length($string) + 12;

        $this->writeLogLine('');

        $this->writeLogLine(str_repeat('*', $length));
        $this->writeLogLine('*     '.$string.'     *');
        $this->writeLogLine(str_repeat('*', $length));

        $this->writeLogLine('');
    }

    private function writeLogLine(?string $content, string $style = null): void
    {
        $this->logger->appendEchoLine($this->replace($content), $style);
    }

    // --------------- content processing --------------

    private function confirmAction(string $question, bool $default = false): bool
    {
        return $this->isForce() || $this->confirm($question, $default);
    }

    private function forceExecuteCommand(string $command)
    {
        $this->runProcessCommand(true, $command);
    }

    private function optionallyExecuteCommand(string $command, callable $callable = null)
    {
        $this->runProcessCommand(false, $command, $callable);
    }

    private function runProcessCommand(bool $forceExecute, string $command, callable $callable = null): void
    {
        $command = $this->replace($command);

        $this->writeLogLine($command, 'info');

        if (!$forceExecute && $this->isOnlyPrint()) {
            return;
        }

        $this->line('running command...'.PHP_EOL);
        $process = Process::fromShellCommandline($command);
        $process->run($callable);
    }

    private function replace(?string $subject, array $replaceMap = null): string
    {
        return $this->replacements->replace($subject ?: '', $replaceMap);
    }

    private function putContentToFile(string $file, array $replace = null): void
    {
        if ($this->isOnlyPrint()) {
            return;
        }

        try {
            $content = $this->replace($this->getContent($file), $replace);

            file_put_contents($file, $content);
        } catch (ErrorException $exception) {
            $this->writeLogLine($exception->getMessage(), 'error');
        }
    }

    private function getContent(string $filename): ?string
    {
        try {
            $content = file_get_contents($filename);
        } catch (ErrorException $exception) {
            $this->writeLogLine('Failed to open file: '.$filename, 'error');
            $content = null;
        }

        return $content;
    }

    private function isOnlyPrint(): bool
    {
        return boolval($this->option('only-print'));
    }

    private function isForce(): bool
    {
        return boolval($this->option('force'));
    }

    private function getStageName(): string
    {
        return $this->argument('stage');
    }
}
