<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Classes;

class Replacements
{
    public static string $prefix = '{{';
    public static string $suffix = '}}';

    public array $replaceMap = [];

    public function __construct(array $replaceMap)
    {
        $this->replaceMap = $replaceMap;
    }

    public function replace(string $subject, array $replaceMap = null)
    {
        $replaceMap = is_null($replaceMap) ? $this->replaceMap : $replaceMap;

        return str_replace(array_keys($replaceMap), array_values($replaceMap), $subject);
    }
}
