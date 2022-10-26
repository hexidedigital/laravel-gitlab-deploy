<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions;

use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Database;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Mail;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Options;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Server;

final class Stage
{
    public function __construct(
        public readonly string $name,
        public readonly Options $options,
        public readonly Server $server,
        public readonly Database $database,
        public readonly ?Mail $mail = null,
    ) {
    }

    public function hasMailOptions(): bool
    {
        return !is_null($this->mail);
    }
}
