<?php

use HexideDigital\GitlabDeploy\Tasks;

return [

    'gitlab-server' => env('GITLAB_HOST', 'gitlab.hexide-digital.com,188.34.141.230'),

    'deployer-php' => base_path('deploy.php'),

    'store-log-folder' => base_path('.deploy/{{STAGE}}/logs'),
    'config-file' => base_path('.deploy/deploy-prepare.yml'),

    'ssh' => [
        // split by directory
        'folder' => base_path('.ssh/{{STAGE}}'),
        'key_name' => 'id_rsa',
        // or put in same with different names
        // 'folder' => base_path('.ssh'),
        // 'key_name' => '{{STAGE}}_id_rsa',
    ],

    'tasks' => [
        Tasks\GenerateSshKeysOnLocalhost::class,
        Tasks\CopySshKeysOnRemoteHost::class,
        Tasks\GenerateSshKeysOnRemoteHost::class,

        Tasks\CreateProjectVariablesOnGitlab::class,
        Tasks\AddGitlabToKnownHostsOnRemoteHost::class,
        Tasks\SaveInitialContentOfDeployFile::class,

        Tasks\PutNewVariablesToDeployFile::class,
        Tasks\PrepareAndCopyDotEnvFileForRemote::class,

        Tasks\RunFirstDeployCommand::class,
        Tasks\RollbackDeployFileContent::class,
        Tasks\InsertCustomAliasesOnRemoteHost::class,

        Tasks\HelpfulSuggestion::class,
    ],

];
