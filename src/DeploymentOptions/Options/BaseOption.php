<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

use Illuminate\Contracts\Support\Arrayable;

abstract class BaseOption implements Arrayable
{
    protected array $source;

    public function __construct(array $source)
    {
        $this->source = $source;

        $this->make($source);
    }

    abstract public function make(array $source): void;

    public function isEmpty(): bool
    {
        return empty($this->source) || $this->onyOfKeyIsEmpty();
    }

    protected function onyOfKeyIsEmpty(): bool
    {
        return sizeof(array_filter($this->toArray(), fn($val) => !$val)) > 0;
    }
}
