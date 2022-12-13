<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use HexideDigital\GitlabDeploy\DeploymentOptions\Options\BaseOption;

final class OptionValidator
{
    public static function onyOfKeyIsEmpty(BaseOption $option): bool
    {
        return collect($option)
            ->reject()
            ->isNotEmpty();
    }
}
