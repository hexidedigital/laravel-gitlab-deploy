<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Loggers;

use Illuminate\Support\Facades\File;

final class FileLogger implements Logger
{
    protected string $fileResourceName;

    /**
     * @param string $fileNameTemplate
     * @param string $timeFormat
     */
    public function __construct(
        protected readonly string $fileNameTemplate,
        protected readonly string $timeFormat = 'Y-m-d-H-i-s',
    ) {
    }

    public function init(): void
    {
        $this->fileResourceName = $this->makeFileName();

        $this->createFile();
    }

    public function line(?string $content = '', ?string $style = null): void
    {
        $content = trim(strip_tags($content ?: ''));

        File::put(
            $this->fileResourceName,
            File::get($this->fileResourceName) . PHP_EOL . $content
        );
    }

    public function newSection(int $step, string $name, int $total): void
    {
        $string = strip_tags("Step - $step/$total. $name");
        $length = mb_strlen($string) + 12;

        $this->line();
        $this->line(str_repeat('*', $length));
        $this->line('*     ' . $string . '     *');
        $this->line(str_repeat('*', $length));
        $this->line();
    }

    protected function createFile(): void
    {
        File::ensureDirectoryExists(File::dirname($this->fileResourceName));

        File::put($this->fileResourceName, '');
    }

    protected function makeFileName(): string
    {
        return rtrim($this->fileNameTemplate, '/') . '/' . date($this->timeFormat) . '.log';
    }
}
