<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Termwind\render;

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
        $this->writeToFile(trim(strip_tags($content ?: '')));

        $this->writeToTerminal($content, $style);
    }

    public function writeToTerminal(?string $content = '', ?string $style = null): void
    {
        $styleMap = [
            'comment' => 'text-comment',
            'error' => 'text-error',
            'info' => 'text-info',
        ];

        $color = Arr::get($styleMap, $style ?: '');

        render(
            <<<HTML
<div class="max-w-150 mx-2 $color">$content</div>
HTML
        );
    }

    public function writeToFile(?string $content = ''): void
    {
        File::put(
            $this->fileResourceName,
            File::get($this->fileResourceName) . PHP_EOL . $content
        );
    }

    public function newSection(int $step, string $name, int $total): void
    {
        $stepName = Str::of($name)->ucfirst()->finish('.')->value();

        $this->writeToTerminal(
            view('gitlab-deploy::console.step', [
                'step' => $step,
                'stepName' => $stepName,
                'total' => $total,
            ])->render()
        );

        $string = strip_tags("Step - $step/$total. $stepName");
        $length = mb_strlen($string) + 12;

        $this->writeToFile();
        $this->writeToFile(str_repeat('*', $length));
        $this->writeToFile('*     ' . $string . '     *');
        $this->writeToFile(str_repeat('*', $length));
        $this->writeToFile();
    }

    protected function makeFileName(): string
    {
        return rtrim($this->fileNameTemplate, '/') . '/' . date($this->timeFormat) . '.log';
    }
}
