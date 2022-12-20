<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Loggers;

interface Logger
{
    public function init(): void;

    public function line(?string $content = '', ?string $style = null): void;

    public function newSection(int $step, string $name, int $total): void;
}
