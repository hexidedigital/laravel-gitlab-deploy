<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Executors\Executor;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\PipeData;
use HexideDigital\GitlabDeploy\Tasks;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Pipeline\Pipeline;
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
    // runtime defined properties
    // ---------------------
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

    public function handle(): int
    {
        try {
            $this->createLogFile();

            $this->executeTasks();
        } catch (Throwable) {
            $this->logger->closeFile();

            return self::FAILURE;
        } finally {
            $this->logger->closeFile();
        }

        return self::SUCCESS;
    }

    private function createLogFile(): void
    {
        $this->logger = new BasicLogger($this);
        $this->logger->openFile();
    }

    /**
     * @throws CircularDependencyException
     * @throws Throwable
     * @throws BindingResolutionException
     * @throws GitlabDeployException
     */
    private function executeTasks(): void
    {
        try {
            // prepare
            $this->state = new DeployerState();
            $this->state->prepare($this->stageName());
            $this->state->setIsPrintOnly($this->isOnlyPrint());

            // begin of process
            $executor = new Executor(
                $this->logger,
                $this->state->getReplacements(),
                $this->isOnlyPrint(),
            );

            $pipeData = new PipeData(
                $this->state,
                $this->logger,
                $executor,
            );

            $prepareTasks = [
                Tasks\GenerateSshKeysOnLocalhost::class,
                Tasks\CopySshKeysOnRemoteHost::class,
                Tasks\GenerateSshKeysOnRemoteHost::class,
                Tasks\CreateProjectVariablesOnGitlab::class,
                Tasks\AddGitlabToKnownHostsOnRemoteHost::class,
                Tasks\SaveInitialContentOfDeployFile::class,
                Tasks\PutNewVariablesToDeployFile::class,
                Tasks\PrepareAndCopyDotEnvFileForRemote::class,
                Tasks\RunFirstDeployCommand::class,
                Tasks\RollbackDeployFileContent::class,
                Tasks\InsertCustomAliasesOnRemoteHost::class,
                Tasks\HelpfulSuggestion::class,
            ];

            app(Pipeline::class)
                ->send($pipeData)
                ->through($prepareTasks);
        } catch (GitlabDeployException $exception) {
            $this->printError('Deploy command unexpected finished.', $exception);
            throw $exception;
        } catch (Throwable $exception) {
            $this->printError('Error happened! See laravel log file.', $exception);
            throw $exception;
        }
    }

    private function printError(string $error, Throwable $exception): void
    {
        $this->logger->appendEchoLine($error, 'error');
        $this->logger->appendEchoLine($exception->getMessage(), 'error');
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
