<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Loggers;

use Illuminate\Support\Str;

class LoggerBag
{
    /**
     * @var array<Logger>
     */
    protected array $loggers = [];
    protected FileLogger $fileLogger;
    protected ConsoleLogger $consoleLogger;

    public function addLogger(Logger $logger): void
    {
        $this->loggers[] = $logger;
    }

    /**
     * @return FileLogger
     */
    public function getFileLogger(): FileLogger
    {
        return $this->fileLogger;
    }

    /**
     * @param FileLogger $fileLogger
     */
    public function setFileLogger(FileLogger $fileLogger): void
    {
        $this->fileLogger = $fileLogger;

        $this->addLogger($this->fileLogger);
    }

    /**
     * @return ConsoleLogger
     */
    public function getConsoleLogger(): ConsoleLogger
    {
        return $this->consoleLogger;
    }

    /**
     * @param ConsoleLogger $consoleLogger
     */
    public function setConsoleLogger(ConsoleLogger $consoleLogger): void
    {
        $this->consoleLogger = $consoleLogger;

        $this->addLogger($this->consoleLogger);
    }

    public function init(): void
    {
        foreach ($this->loggers as $logger) {
            $logger->init();
        }
    }

    public function line(?string $content = '', string $style = null): void
    {
        foreach ($this->loggers as $logger) {
            $logger->line($content, $style);
        }
    }

    public function newSection(int $step, string $name, int $total): void
    {
        $stepName = Str::of($name)->ucfirst()->finish('.')->value();

        foreach ($this->loggers as $logger) {
            $logger->newSection($step, $stepName, $total);
        }
    }
}
