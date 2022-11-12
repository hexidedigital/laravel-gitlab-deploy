<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

use Illuminate\Contracts\Support\Arrayable;

abstract class BaseOption implements Arrayable
{
    abstract public function __construct(array $source);

    public function isEmpty(): bool
    {
        return $this->onyOfKeyIsEmpty();
    }

    public function onyOfKeyIsEmpty(): bool
    {
        return sizeof(array_filter($this->toArray(), fn ($val) => !$val)) > 0;
    }
}
