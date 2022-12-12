<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers\Builders;

use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Helpers\Replacements;

final class ReplacementsBuilder
{
    private Replacements $replacements;

    public function __construct(
        private readonly Stage $stage,
    ) {
    }

    public function getReplacements(): Replacements
    {
        return $this->replacements;
    }

    public function build(): ReplacementsBuilder
    {
        $this->replacements = new Replacements();

        // server - USER HOST SSH_PORT DEPLOY_DOMAIN DEPLOY_SERVER DEPLOY_USER DEPLOY_PASS
        $this->replacements->merge($this->stage->server->toReplacesArray());


        /*-----------------------
         * step 2
         *
         * options - CI_REPOSITORY_URL DEPLOY_BASE_DIR BIN_PHP BIN_COMPOSER
         * database - DB_DATABASE DB_USERNAME DB_PASSWORD
         * mail - MAIL_HOSTNAME MAIL_USER MAIL_PASSWORD
         *
         * other - PROJ_DIR CI_COMMIT_REF_NAME
         */
        $data = array_merge(
            $this->stage->options->toReplacesArray(),
            $this->stage->database->toReplacesArray(),
            $this->stage->hasMailOptions() ? $this->stage->mail->toReplacesArray() : [],
            [
                'PROJ_DIR' => base_path(),
                'CI_COMMIT_REF_NAME' => $this->stage->name,
                'STAGE' => $this->stage->name,

                'DEPLOY_BASE_DIR' => $this->replacements->replace($this->stage->options->baseDir),
            ],
        );
        $this->replacements->merge($data);


        /*-----------------------
         * step 3
         */
        $this->replacements->merge([
            'DEPLOY_PHP_ENV' => <<<PHP
\$CI_REPOSITORY_URL = "{{CI_REPOSITORY_URL}}";
\$CI_COMMIT_REF_NAME = "{{CI_COMMIT_REF_NAME}}";
\$BIN_PHP = "{{BIN_PHP}}";
\$BIN_COMPOSER = "{{BIN_COMPOSER}}";
\$DEPLOY_BASE_DIR = "{{DEPLOY_BASE_DIR}}";
\$DEPLOY_SERVER = "{{DEPLOY_SERVER}}";
\$DEPLOY_USER = "{{DEPLOY_USER}}";
\$SSH_PORT = "{{SSH_PORT}}";
PHP,
        ]);

        $filePath = str(config('gitlab-deploy.ssh.folder'))
            ->finish('/')
            ->append(config('gitlab-deploy.ssh.key_name'))
            ->value();

        $this->replacements->merge([
            'IDENTITY_FILE' => $filePath,
            'IDENTITY_FILE_PUB' => "$filePath.pub",

            'remoteSshCredentials' => '-i "{{IDENTITY_FILE}}" -p {{SSH_PORT}} "{{DEPLOY_USER}}@{{DEPLOY_SERVER}}"',
            'remoteScpOptions' => '-i "{{IDENTITY_FILE}}" -P {{SSH_PORT}}',
        ]);

        $this->replacements->merge($data);

        return $this;
    }
}
