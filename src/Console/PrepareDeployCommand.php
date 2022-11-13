<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Executors\Executor;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\DeployerFileContent;
use HexideDigital\GitlabDeploy\Tasks\AddGitlabToKnownHostsOnRemoteHost;
use HexideDigital\GitlabDeploy\Tasks\CopySshKeysOnRemoteHost;
use HexideDigital\GitlabDeploy\Tasks\CreateProjectVariablesOnGitlab;
use HexideDigital\GitlabDeploy\Tasks\GenerateSshKeysOnLocalhost;
use HexideDigital\GitlabDeploy\Tasks\GenerateSshKeysOnRemoteHost;
use HexideDigital\GitlabDeploy\Tasks\HelpfulSuggestion;
use HexideDigital\GitlabDeploy\Tasks\InsertCustomAliasesOnRemoteHost;
use HexideDigital\GitlabDeploy\Tasks\PrepareAndCopyDotEnvFileForRemote;
use HexideDigital\GitlabDeploy\Tasks\PutNewVariablesToDeployFile;
use HexideDigital\GitlabDeploy\Tasks\RunFirstDeployCommand;
use HexideDigital\GitlabDeploy\Tasks\Task;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

class PrepareDeployCommand extends Command
{
    // ---------------------
    // only to describe command
    // ---------------------
    protected $name = 'deploy:gitlab';
    protected $description = 'Command to prepare your deploy';


    // ---------------------
    // editable across executing
    // ---------------------
    protected int $step = 1;

    // ---------------------
    // runtime defined properties
    // ---------------------
    protected Filesystem $filesystem;
    protected DeployerState $state;

    protected BasicLogger $logger;
    protected Executor $executor;


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
            $this->state->prepare($this->stageName());
            $this->state->setIsPrintOnly($this->isOnlyPrint());

            // begin of process
            $this->executor = new Executor(
                $this->logger,
                $this->state->getReplacements(),
                $this->isOnlyPrint(),
            );

            $prepareTasks = [
                GenerateSshKeysOnLocalhost::class,
                CopySshKeysOnRemoteHost::class,
                GenerateSshKeysOnRemoteHost::class,
                CreateProjectVariablesOnGitlab::class,
                AddGitlabToKnownHostsOnRemoteHost::class,
            ];

            foreach ($prepareTasks as $task) {
                $this->executeTask($task);
            }

            $deployerTasks = [
                PutNewVariablesToDeployFile::class,
                PrepareAndCopyDotEnvFileForRemote::class,
                RunFirstDeployCommand::class,
            ];

            $deployerContent = $this->saveInitialContentOfDeployFile();
            foreach ($deployerTasks as $task) {
                $this->executeTask($task);
            }
            $this->rollbackDeployFileContent($deployerContent);

            $finishTasks = [
                InsertCustomAliasesOnRemoteHost::class,
                HelpfulSuggestion::class,
            ];
            foreach ($finishTasks as $task) {
                $this->executeTask($task);
            }
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
        $this->logger->appendEchoLine($error, 'error');
        $this->logger->appendEchoLine($exception->getMessage(), 'error');
    }

    private function createLogFile(): void
    {
        $this->logger = new BasicLogger($this);
        $this->logger->openFile();
    }

    /**
     * @throws GitlabDeployException
     */
    private function saveInitialContentOfDeployFile(): DeployerFileContent
    {
        $deployerContent = new DeployerFileContent(config('gitlab-deploy.deployer-php'));

        $deployerContent->backup($this->isOnlyPrint());

        return $deployerContent;
    }

    private function rollbackDeployFileContent(DeployerFileContent $content): void
    {
        $this->writeLogLine('Rollback deploy file content', 'comment');

        $content->restore();
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
        $task->setExecutor($this->executor);

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
        $this->logger->appendEchoLine($this->state->getReplacements()->replace($content), $style);
    }

    // --------------- content processing --------------

    private function isOnlyPrint(): bool
    {
        return boolval($this->option('only-print'));
    }

    private function stageName(): string
    {
        return $this->argument('stage');
    }
}
