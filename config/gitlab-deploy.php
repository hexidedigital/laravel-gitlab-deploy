<?php

use HexideDigital\GitlabDeploy\Tasks;

return [

    /*
    |--------------------------------------------------------------------------
    | GitLab server
    |--------------------------------------------------------------------------
    |
    | Uses for `ssh-keyscan` command to fetch public keys
    | and put to known hosts file on remote server
    |
    */
    'gitlab-server' => env('GITLAB_HOST', 'gitlab.hexide-digital.com'),

    /*
    |--------------------------------------------------------------------------
    | Deployer file
    |--------------------------------------------------------------------------
    |
    | Path to deployer file
    |
    */
    'deployer-php' => base_path('deploy.php'),

    /*
    |--------------------------------------------------------------------------
    | Working directory
    |--------------------------------------------------------------------------
    |
    | Uses for store logs, env and other files across execution
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Deploy prepare tasks
    |--------------------------------------------------------------------------
    |
    | List of tasks to execute on deploy prepare command.
    | You can edit this list as you want.
    | To add new task extend from `HexideDigital\GitlabDeploy\Tasks\BaseTask`
    |
    */
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
