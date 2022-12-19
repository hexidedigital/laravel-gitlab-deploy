<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BasicLogger
{
    protected string $fileResourceName;
    protected Command $command;

    protected readonly string $fileNameTemplate;
    protected readonly string $timeFormat;

    /**
     * @param Command $command
     * @param string $timeFormat
     * @param string $fileNameTemplate
     */
    public function __construct(
        Command $command,
        string $fileNameTemplate,
        string $timeFormat = 'Y-m-d-H-i-s',
    ) {
        $this->command = $command;
        $this->fileNameTemplate = $fileNameTemplate;
        $this->timeFormat = $timeFormat;
    }

    public function openFile(): void
    {
        $fileName = $this->makeFileName();

        File::ensureDirectoryExists(File::dirname($fileName));

        $this->fileResourceName = $fileName;

        File::put($this->fileResourceName, '');
    }

    public function appendEchoLine(?string $content = '', string $style = null): void
    {
        $this->writeToFile(strip_tags($content ?: ''));

        $this->command->line($content ?: '', $style);
    }

    public function writeToFile(?string $content = ''): void
    {
        File::put(
            $this->fileResourceName,
            File::get($this->fileResourceName) . PHP_EOL . $content
        );
    }

    public function newSection(int $step, string $name): void
    {
        $string = strip_tags($step . '. ' . Str::ucfirst($name));

        $length = Str::length($string) + 12;

        $this->appendEchoLine();
        $this->appendEchoLine(str_repeat('*', $length));
        $this->appendEchoLine('*     ' . $string . '     *');
        $this->appendEchoLine(str_repeat('*', $length));
        $this->appendEchoLine();
    }

    protected function makeFileName(): string
    {
        return rtrim($this->fileNameTemplate, '/') . '/' . date($this->timeFormat) . '.log';
    }
}
