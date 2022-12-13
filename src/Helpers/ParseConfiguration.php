<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use Symfony\Component\Yaml\Yaml;

final class ParseConfiguration
{
    /**
     * @param string $filePath
     * @return mixed
     */
    public function parseFile(string $filePath): mixed
    {
        return Yaml::parseFile($filePath);
    }
}
