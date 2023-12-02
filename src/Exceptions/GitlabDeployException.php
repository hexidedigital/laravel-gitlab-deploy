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
            'To process deploy prepare you must specify Gitlab credentials - ' . implode(', ', $values)
        );
    }

    public static function unsupportedConfigurationVersion(string $actual): GitlabDeployException
    {
        return new GitlabDeployException(
            "Unsupported configuration version [$actual]. " .
            "Please update your configuration file to the latest version",
        );
    }
}
