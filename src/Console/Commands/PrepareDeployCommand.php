<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Console\Commands;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Loggers\ConsoleLogger;
use HexideDigital\GitlabDeploy\Loggers\FileLogger;
use HexideDigital\GitlabDeploy\Loggers\LoggerBag;
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

use function Termwind\{render, style};

class PrepareDeployCommand extends Command
{
    protected $name = 'deploy:gitlab';

    protected $description = 'Command to prepare your deploy';

    protected LoggerBag $logger;

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function handle(): int
    {
        $this->setUpStyles();

        render(view('gitlab-deploy::console.logo')->render());

        $this->infoLine('🛠 Preparing command.');

        try {
            $this->createLoggers();

            $this->executeTasks();

            $this->infoLine('🎉 Command successfully finished!');
        } catch (GitlabDeployException $exception) {
            $this->printError('🤕 Deploy command failed.', $exception);

            report($exception);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function setUpStyles(): void
    {
        style('text-command')->apply('text-orange-500');
        style('text-comment')->apply('text-blue-500');
        style('text-info')->apply('text-lime-500');
        style('text-error')->apply('text-red-500');
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

    protected function createLoggers(): void
    {
        $this->logger = new LoggerBag();
        $this->logger->addLogger(new FileLogger(config('gitlab-deploy.store-log-folder')));
        $this->logger->addLogger(new ConsoleLogger());

        $this->logger->init();
    }

    /**
     * @throws CircularDependencyException
     * @throws Throwable
     * @throws BindingResolutionException
     * @throws GitlabDeployException
     */
    protected function executeTasks(): void
    {
        $this->infoLine('👀 Fetching available tasks...');

        $tasksToExecute = $this->getTasksToExecute();

        if ($tasksToExecute->isEmpty()) {
            throw new GitlabDeployException('🤨 Tasks list is empty!');
        }

        $pipeData = $this->preparePipeData($tasksToExecute->count());

        $this->infoLine('🤖 Running tasks...');

        app(Pipeline::class)
            ->send($pipeData)
            ->through($tasksToExecute->all())
            ->thenReturn();
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     * @throws GitlabDeployException
     */
    protected function preparePipeData(int $tasksToExecute): PipeData
    {
        $state = $this->makeState();

        $executor = $this->getExecutor($state);

        return new PipeData(
            $state,
            $this->logger,
            $executor,
            $this,
            $tasksToExecute,
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
        $this->logger->line($error, 'error');
        $this->logger->line(
            <<<HTML
<span class="font-bold my-1">{$exception->getMessage()}</span>
HTML
            ,
            'error'
        );
    }

    protected function isOnlyPrint(): bool
    {
        return boolval($this->option('only-print'));
    }

    protected function stageName(): string
    {
        return $this->argument('stage');
    }

    protected function infoLine(string $content): void
    {
        $this->renderLine(
            <<<HTML
<div class="text-info">$content</div>
HTML
        );
    }

    protected function renderLine(string $content): void
    {
        render(
            <<<HTML
<div class="max-w-150 mx-2">$content</div>
HTML
        );
    }
}
