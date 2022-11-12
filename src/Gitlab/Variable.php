<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Gitlab;

use Illuminate\Contracts\Support\Arrayable;

final class Variable implements Arrayable
{
    public readonly string $value;

    public function __construct(
        public readonly string $key,
        public readonly string $scope,
        ?string $value,
    ) {
        $this->value = strval($value);
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'scope' => $this->scope,
        ];
    }
}
