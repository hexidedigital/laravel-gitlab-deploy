<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use Illuminate\Console\Command;

class BasicLogger
{
    /** @var resource */
    protected $fileResource;
    protected Command $command;

    protected readonly string $timeFormat;
    protected readonly string $fileName;

    /**
     * @param string $timeFormat
     * @param string $fileName
     */
    public function __construct(
        string $timeFormat = 'Y-m-d-H-i-s',
        string $fileName = '.deploy/dep-log.',
    )
    {
        $this->timeFormat = $timeFormat;
        $this->fileName = $fileName;
    }

    public function openFile(): void
    {
        $this->fileResource = fopen(base_path($this->fileName.date($this->timeFormat).'.log'), 'w');
    }

    public function closeFile(): void
    {
        fclose($this->fileResource);
    }

    public function appendEchoLine(?string $content, string $style = null): void
    {
        $this->writeToFile(strip_tags($content ?: ''));

        $this->command->line($content ?: '', $style);
    }

    public function writeToFile(?string $content): void
    {
        fwrite($this->fileResource, $content.PHP_EOL);
    }
}
