<?php

namespace HexideDigital\GitlabDeploy\Contracts;

use HexideDigital\GitlabDeploy\DeployOptions\Options;

interface ParsesConfiguration
{
    public function parseFile(string $filePath, string $stage): void;

    public function getStageName(): string;

    public function parseStageOptions(array $allOptions): void;

    public function parseGitlabCredentials(array $gitlab);

    public function getOptions(): Options;
}
