<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

final class Replacements
{
    private static string $prefix = '{{';
    private static string $suffix = '}}';

    private array $placeholders = [];

    public function __construct(array $placeholders = [])
    {
        $this->merge($placeholders);
    }

    public function merge(array $placeholders = []): void
    {
        collect($placeholders)
            ->each(fn (?string $placeholder, string $key) => $this->add($key, strval($placeholder)));
    }

    public function add(string $key, string $placeholder): void
    {
        $this->placeholders[$key] = $placeholder;
    }

    public function get(string $key): string
    {
        return $this->placeholders[$key] ?? " ---|$key|--- ";
    }

    public function replace(?string $subject, array $additionalPlaceholders = []): string
    {
        $this->merge($additionalPlaceholders ?: []);

        return $this->interpolate($subject ?: '');
    }

    public function interpolate(string $subject, int $level = 1): string
    {
        if ($level > $this->getMaxReplaceLevelDeep()) {
            return $subject;
        }

        $pattern = $this->makePattern();

        return preg_replace_callback($pattern, function ($match) use ($level) {
            [$fullTagMatch, $tagName] = $match;

            // Do not replace tags with :keep
            if (strpos($tagName, ':keep')) {
                return str_replace(':keep', '', $fullTagMatch);
            }

            // Replace tags with only one time
            if (str_starts_with($tagName, '!')) {
                return $this->get(rtrim($tagName, '!'));
            }

            return $this->interpolate($this->get($tagName), $level + 1);
        }, $subject);
    }

    public static function getSuffix(): string
    {
        return self::$suffix;
    }

    public static function getPrefix(): string
    {
        return self::$prefix;
    }

    public function getMaxReplaceLevelDeep(): int
    {
        return 5;
    }

    public function makePattern(): string
    {
        $prefix = self::getPrefix();
        $suffix = self::getSuffix();

        return "/$prefix([^$suffix]*)$suffix/";
    }
}
