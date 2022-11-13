<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @extends Arrayable<string, string|int|bool|null>
 */
interface BaseOption extends Arrayable
{
    public function __construct(array $source);

    public function toReplacesArray(): array;
}
