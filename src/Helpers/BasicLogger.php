<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BasicLogger
{
    /** @var resource */
    protected $fileResource;
    protected Command $command;

    protected readonly string $timeFormat;
    protected readonly string $fileName;

    /**
     * @param Command $command
     * @param string $timeFormat
     * @param string $fileName
     */
    public function __construct(
        Command $command,
        string $timeFormat = 'Y-m-d-H-i-s',
        string $fileName = '.deploy/dep-log.',
    ) {
        $this->command = $command;
        $this->timeFormat = $timeFormat;
        $this->fileName = $fileName;
    }

    public function openFile(): void
    {
        $this->fileResource = fopen(base_path($this->fileName . date($this->timeFormat) . '.log'), 'w');
    }

    public function closeFile(): void
    {
        if (!is_resource($this->fileResource)) {
            return;
        }

        fclose($this->fileResource);
    }

    public function appendEchoLine(?string $content = '', string $style = null): void
    {
        $this->writeToFile(strip_tags($content ?: ''));

        $this->command->line($content ?: '', $style);
    }

    public function writeToFile(?string $content = ''): void
    {
        fwrite($this->fileResource, $content . PHP_EOL);
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
}
