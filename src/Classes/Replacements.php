<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Classes;

class Replacements
{
    public static string $prefix = '{{';
    public static string $suffix = '}}';

    private array $replaceMap = [];

    public function __construct(array $replaceMap)
    {
        $this->mergeReplaces($replaceMap);
    }

    public function mergeReplaces(array $replaces = [])
    {
        $this->replaceMap = array_merge($this->replaceMap, $replaces);
    }

    public function replace(string $subject, array $replaceMap = null)
    {
        $replaceMap = is_null($replaceMap) ? $this->replaceMap : $replaceMap;

        return str_replace(array_keys($replaceMap), array_values($replaceMap), $subject);
    }
}
