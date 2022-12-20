<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Loggers;

use Illuminate\Support\Arr;

use function Termwind\render;

final class ConsoleLogger implements Logger
{
    public function init(): void
    {
    }

    public function line(?string $content = '', ?string $style = null): void
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

    public function newSection(int $step, string $name, int $total): void
    {
        $this->line(
            view('gitlab-deploy::console.step', [
                'step' => $step,
                'stepName' => $name,
                'total' => $total,
            ])->render()
        );
    }
}
