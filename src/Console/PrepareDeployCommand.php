<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console;

use ErrorException;
use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Gitlab\Tasks\GitlabVariablesCreator;
use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\ParseConfiguration;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\Helpers\ReplacementsBuilder;
use HexideDigital\GitlabDeploy\Helpers\VariableBagBuilder;
use HexideDigital\GitlabDeploy\Tasks\HelpfulSuggestion;
use HexideDigital\GitlabDeploy\Tasks\Task;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Throwable;

class PrepareDeployCommand extends Command
{
    // ---------------------
    // only to describe command
    // ---------------------
    protected $name = 'deploy:gitlab';
    protected $description = 'Command to prepare your deploy';

    // ---------------------
    // static patterns for replaces
    // ---------------------
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
    protected readonly Filesystem $filesystem;
    protected readonly DeployerState $state;

    protected readonly BasicLogger $logger;

    protected readonly string $deployInitialContent;

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

    public function __construct(
        Filesystem $filesystem,
    ) {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    public function handle(): int
    {
        $this->createLogFile();

        $finishedWithError = false;

        try {
            // prepare
            $this->state = new DeployerState();

            $this->parseConfigurations($this->state);
            $this->setupReplacements($this->state);

            $this->state->setupGitlabVariables();

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
        } catch (Throwable $exception) {
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

    private function printError(string $error, Throwable $exception): void
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
    private function parseConfigurations(DeployerState $state): void
    {
        $parser = app(ParseConfiguration::class);

        $parser->parseFile(config('gitlab-deploy.config-file'));

        $state->setConfigurations($parser->configurations);
        $state->setStage($parser->configurations->stageBag->get($this->getStageName()));
    }

    private function setupReplacements(DeployerState $state): void
    {
        $builder = new ReplacementsBuilder($state->getStage());

        $replacements = $builder->build()->getReplacements();

        $filePath = $replacements->replace(
            \str(config('gitlab-deploy.ssh.folder'))
                ->finish('/')
                ->append(config('gitlab-deploy.ssh.key_name'))
        );

        $replacements->mergeReplaces([
            '{{IDENTITY_FILE}}' => $filePath,
            '{{IDENTITY_FILE_PUB}}' => "$filePath.pub",
        ]);

        $this->state->setReplacements($replacements);
    }

    /**
     * @throws GitlabDeployException
     */
    private function task_saveInitialContentOfDeployFile(): string
    {
        $initialContent = $this->getContent(config('gitlab-deploy.deployer-php'));

        if (empty($initialContent)) {
            throw new GitlabDeployException('Deploy file is empty or not exists.');
        }

        return $initialContent;
    }

    private function task_generateSshKeysOnLocalhost(): void
    {
        $this->newSection('generate ssh keys - private key to gitlab (localhost)');

        $this->forceExecuteCommand('mkdir -p '. $this->replace(config('gitlab-deploy.ssh.folder')));

        if (!$this->isSshFilesExits() || $this->confirmAction('Should generate and override existed key?')) {
            $option = $this->isSshFilesExits() ? '-y' : '';
            $this->optionallyExecuteCommand('ssh-keygen -t rsa -f "{{IDENTITY_FILE}}" -N "" '.$option);
        }

        $this->writeLogLine('cat {{IDENTITY_FILE}}', 'info');

        $content = $this->getContent($this->replace('{{IDENTITY_FILE}}'));

        $variable = new Variable(
            key: 'SSH_PRIVATE_KEY',
            scope: $this->state->getStage()->name,
            value: $content
        );

        $this->state->getGitlabVariablesBag()->add($variable->key, $variable);
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

        $pubKeyContent = '';
        $this->optionallyExecuteCommand(
            $sshRemote.' "cat ~/.ssh/id_rsa.pub"',
            function ($type, $buffer) use (&$pubKeyContent) {
                $pubKeyContent = $buffer;
            }
        );

        $pubKeyVariable = new Variable(
            key: 'SSH_PUB_KEY',
            scope: $this->state->getStage()->name,
            value: $pubKeyContent
        );

        $this->state->getGitlabVariablesBag()->add($pubKeyVariable->key, $pubKeyVariable);

        $this->writeLogLine('Remote pub-key: '.$pubKeyVariable->value, 'info');
    }

    private function task_createProjectVariablesOnGitlab(): void
    {
        $this->newSection('gitlab variables');

        // print to file on case if error happens
        $rows = [];
        $variableBag = $this->state->getGitlabVariablesBag();
        foreach ($variableBag->except($variableBag->printAloneKeys()) as $variable) {
            $this->logger->writeToFile($variable->key.PHP_EOL.$variable->value.PHP_EOL);

            $rows[] = [$variable->key, $variable->value];
        }

        foreach ($variableBag->only($variableBag->printAloneKeys()) as $variable) {
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
            ->setProject($this->state->getConfigurations()->project)
            ->setVariableBag($variableBag);

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
            'ssh-keyscan -t ecdsa-sha2-nistp256 '.config('gitlab-deploy.gitlab-server'),
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

        $this->putContentToFile(config('gitlab-deploy.deployer-php'), [
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

        $mail = $this->state->getStage()->hasMailOptions()
            ? [
                'MAIL_HOST=mailhog' => $this->replace('MAIL_HOST={{MAIL_HOSTNAME}}'),
                'MAIL_PORT=1025' => 'MAIL_PORT=587',
                'MAIL_USERNAME=null' => $this->replace('MAIL_USERNAME={{MAIL_USER}}'),
                'MAIL_PASSWORD=null' => $this->replace('MAIL_PASSWORD={{MAIL_PASSWORD}}'),
                'MAIL_ENCRYPTION=null' => 'MAIL_ENCRYPTION=tls',
                'MAIL_FROM_ADDRESS=null' => $this->replace('MAIL_FROM_ADDRESS={{MAIL_USER}}'),
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
            $sharedDir = '{{DEPLOY_BASE_DIR}}/shared';
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

        $this->filesystem->put(config('gitlab-deploy.deployer-php'), $this->deployInitialContent);
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

        $aliasesPath = $this->replace(storage_path('deployer/.bash_aliases-{{STAGE}}'));
        $aliasesLoader = <<<SHELL
if [ -f  ~/.bash_aliases ];
    then . ~/.bash_aliases
fi
SHELL;

        $this->optionallyExecuteCommand("cp $filePath $aliasesPath");
        $this->putContentToFile($aliasesPath);

        $this->writeLogLine('Optionally, copy next script to load aliases into ~/.bashrc file.', 'comment');
        $this->writeLogLine($aliasesLoader);

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
        $this->executeTask(HelpfulSuggestion::class);
    }

    /**
     * @param class-string<Task> $taskClass
     * @return void
     */
    private function executeTask(string $taskClass): void
    {
        $task = $this->prepareTask(app($taskClass));

        $this->newSection($task->getTaskName());

        $task->execute();
    }

    private function prepareTask(Task $task): Task
    {
        $task->setState($this->state);
        $task->setLogger($this->logger);

        return $task;
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
        return $this->state->getReplacements()->replace($subject ?: '', $replaceMap);
    }

    private function putContentToFile(string $path, array $replace = null): void
    {
        if ($this->isOnlyPrint()) {
            return;
        }

        try {
            $contents = $this->replace($this->getContent($path), $replace);

            $this->filesystem->put($path, $contents);
        } catch (ErrorException $exception) {
            $this->writeLogLine($exception->getMessage(), 'error');
        }
    }

    private function getContent(string $filename): ?string
    {
        try {
            $contents = $this->filesystem->get($filename);
        } catch (ErrorException $exception) {
            $this->writeLogLine('Failed to open file: '.$filename, 'error');
            $contents = null;
        }

        return $contents;
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
