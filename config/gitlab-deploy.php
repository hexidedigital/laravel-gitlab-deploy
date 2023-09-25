<?php

use HexideDigital\GitlabDeploy\Tasks;

return [

    'gitlab-server' => env('GITLAB_HOST', 'gitlab.hexide-digital.com,188.34.141.230'),

    'deployer-php' => base_path('deploy.php'),

    'working-dir' => base_path('.deploy'),
    'store-log-folder' => base_path('.deploy/logs'),
    'config-file' => base_path('.deploy/deploy-prepare.yml'),

    'ssh' => [
        'key_name' => 'id_rsa',
        // store ssh keys in a project folder (don't forget to add to gitignore)
        'folder' => base_path('.ssh'),
        // use an ssh key of your machine
        // 'folder' => '~/.ssh',
        // split by directory
        // 'folder' => base_path('.ssh/{{STAGE}}'),
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
