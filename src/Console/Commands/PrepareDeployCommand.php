<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console\Commands;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\PipeData;
use HexideDigital\GitlabDeploy\ProcessExecutors\BasicExecutor;
use HexideDigital\GitlabDeploy\ProcessExecutors\Executor;
use HexideDigital\GitlabDeploy\ProcessExecutors\NullExecutor;
use HexideDigital\GitlabDeploy\Tasks\Task;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\LazyCollection;
use ReflectionClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

class PrepareDeployCommand extends Command
{
    protected $name = 'deploy:gitlab';

    protected $description = 'Command to prepare your deploy';

    protected BasicLogger $logger;

    public function handle(): int
    {
        $this->info('Start-upping...');

        try {
            $this->createLogFile();

            $this->executeTasks();
        } catch (Throwable $exception) {
            $this->error("Command finished with unexpected exception - <info>{$exception->getMessage()}</info>");

            return self::FAILURE;
        }

        $this->info('Command successfully finished!');

        return self::SUCCESS;
    }

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

    protected function createLogFile(): void
    {
        $this->logger = new BasicLogger($this, config('gitlab-deploy.store-log-folder'));
        $this->logger->openFile();
    }

    /**
     * @throws CircularDependencyException
     * @throws Throwable
     * @throws BindingResolutionException
     * @throws GitlabDeployException
     */
    protected function executeTasks(): void
    {
        try {
            $this->info('Fetching available tasks...');

            $tasksToExecute = $this->getTasksToExecute();

            if ($tasksToExecute->isEmpty()) {
                throw new GitlabDeployException('Tasks list is empty!');
            }

            $pipeData = $this->preparePipeData();

            $this->info('Running tasks...');

            app(Pipeline::class)
                ->send($pipeData)
                ->through($tasksToExecute->all())
                ->thenReturn();
        } catch (GitlabDeployException $exception) {
            $this->printError('Deploy command unexpected finished.', $exception);

            throw $exception;
        } catch (Throwable $exception) {
            $this->printError('Error happened! See laravel log file.', $exception);

            throw $exception;
        }
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws GitlabDeployException
     */
    protected function preparePipeData(): PipeData
    {
        $state = $this->makeState();

        $executor = $this->getExecutor($state);

        return new PipeData(
            $state,
            $this->logger,
            $executor,
            $this
        );
    }

    /**
     * @param DeployerState $state
     * @return Executor
     */
    protected function getExecutor(DeployerState $state): Executor
    {
        if ($this->isOnlyPrint()) {
            $executor = new NullExecutor(
                $this->logger,
                $state->getReplacements(),
            );
        } else {
            $executor = new BasicExecutor(
                $this->logger,
                $state->getReplacements(),
            );
        }

        return $executor;
    }

    /**
     * @return DeployerState
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws GitlabDeployException
     */
    protected function makeState(): DeployerState
    {
        $state = new DeployerState();
        $state->prepare($this->stageName());
        $state->setIsPrintOnly($this->isOnlyPrint());

        return $state;
    }

    /**
     * @return LazyCollection<Task>
     */
    protected function getTasks(): LazyCollection
    {
        return new LazyCollection(function () {
            foreach (config('gitlab-deploy.tasks', []) as $taskClass) {
                if (is_subclass_of($taskClass, Task::class) &&
                    !(new ReflectionClass($taskClass))->isAbstract()) {
                    yield $taskClass;
                }
            }
        });
    }

    /**
     * @return LazyCollection<Task>
     */
    protected function getTasksToExecute(): LazyCollection
    {
        return new LazyCollection(function () {
            foreach ($this->getTasks() as $taskName) {
                $task = app($taskName);

                if ($this->isOnlyPrint() && !$task->shouldRunInPrintMode()) {
                    continue;
                }

                yield $task;
            }
        });
    }

    protected function printError(string $error, Throwable $exception): void
    {
        $this->logger->appendEchoLine($error, 'error');
        $this->logger->appendEchoLine($exception->getMessage(), 'error');
    }

    protected function isOnlyPrint(): bool
    {
        return boolval($this->option('only-print'));
    }

    protected function stageName(): string
    {
        return $this->argument('stage');
    }
}
