<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Gitlab;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;

final class GitlabProject
{
    /** @throws GitlabDeployException */
    public function __construct(
        public readonly string $id,
        public readonly string $token,
        public readonly string $url,
    ) {
        if (empty($this->token)) {
            throw new GitlabDeployException('Provide api token for Gitlab project');
        }

        if (empty($this->url)) {
            throw new GitlabDeployException('Provide domain url for Gitlab project');
        }
    }
}
