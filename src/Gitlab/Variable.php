<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Gitlab;

final class Variable
{
    public readonly string $value;

    public function __construct(
        public readonly string $key,
        public readonly string $scope,
        mixed $value,
    ) {
        $this->value = strval($value);
    }
}
