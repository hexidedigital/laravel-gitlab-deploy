<?php

use HexideDigital\GitlabDeploy\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function safeUnlink(string $path): void
{
    if (File::isFile($path)) {
        unlink($path);
    }
}
