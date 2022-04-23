<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeployOptions;

use Illuminate\Contracts\Support\Arrayable;

abstract class OptionsContainer implements Arrayable
{
    protected array $source;

    public function __construct(array $source)
    {
        $this->source = $source;

        $this->makeFromSource($source);
    }

    abstract public function makeFromSource(array $source);

    public function isEmpty(): bool
    {
        return empty($this->source) || $this->onyOfKeyIsEmpty();
    }

    protected function onyOfKeyIsEmpty(): bool
    {
        return sizeof(array_filter($this->toArray())) > 0;
    }
}
