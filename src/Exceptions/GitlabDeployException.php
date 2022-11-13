<?php

namespace HexideDigital\GitlabDeploy\Exceptions;

use Exception;

class GitlabDeployException extends Exception
{
    public static function hasEmptyStageOptions(string $stageName, array $emptyOptions): GitlabDeployException
    {
        $options = implode(', ', $emptyOptions);

        return new GitlabDeployException(
            "To process deploy prepare you must specify all values for stage [$stageName]. Empty options: $options"
        );
    }

    public static function emptyGitlabProjectCredentials(array $values): GitlabDeployException
    {
        return new GitlabDeployException(
            'To process deploy prepare you must specify gitlab credentials - ' . implode(', ', $values)
        );
    }
}
