<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

final class Replacements
{
    public static string $prefix = '{{';
    public static string $suffix = '}}';

    private array $replaceMap = [];

    public function __construct(array $replaceMap = [])
    {
        $this->mergeReplaces($replaceMap);
    }

    public function mergeReplaces(array $replaces = []): void
    {
        foreach ($replaces as $key => $replace) {
            try {
                $this->replaceMap[$key] = $this->replace(strval($replace));
            } catch (\Error $error) {
                dd($replaces, $key, $replace);
            }
        }
    }

    public function replace(string $subject, array $replaceMap = null): array|string
    {
        $replaceMap = is_null($replaceMap) ? $this->replaceMap : $replaceMap;

        return str_replace(array_keys($replaceMap), array_values($replaceMap), $subject);
    }
}
