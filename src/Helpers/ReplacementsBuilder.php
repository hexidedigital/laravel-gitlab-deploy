<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;

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
        /*-----------------------
         * step 1
         *
         * server - USER HOST SSH_PORT DEPLOY_DOMAIN DEPLOY_SERVER DEPLOY_USER DEPLOY_PASS
         */
        $this->replacements = new Replacements(
            $this->stage->server->toArray()
        );

        /*-----------------------
         * step 2
         *
         * options - CI_REPOSITORY_URL DEPLOY_BASE_DIR BIN_PHP BIN_COMPOSER
         * database - DB_DATABASE DB_USERNAME DB_PASSWORD
         * mail - MAIL_HOSTNAME MAIL_USER MAIL_PASSWORD
         *
         * other - PROJ_DIR CI_COMMIT_REF_NAME
         */
        $this->replacements->mergeReplaces(
            array_merge(
                $this->stage->options->toArray(),
                $this->stage->database->toArray(),
                $this->stage->hasMailOptions() ? $this->stage->mail->toArray() : [],
                [
                    '{{PROJ_DIR}}' => base_path(),
                    '{{CI_COMMIT_REF_NAME}}' => $this->stage->name,
                    '{{STAGE}}' => $this->stage->name,

                    '{{DEPLOY_BASE_DIR}}' => $this->replacements->replace($this->stage->options->baseDir),
                ],
            )
        );

        /*-----------------------
         * step 3
         */
        $this->replacements->mergeReplaces([
            '{{DEPLOY_PHP_ENV}}' => $this->replacements->replace(
                <<<PHP
\$CI_REPOSITORY_URL = "{{CI_REPOSITORY_URL}}";
\$CI_COMMIT_REF_NAME = "{{CI_COMMIT_REF_NAME}}";
\$BIN_PHP = "{{BIN_PHP}}";
\$BIN_COMPOSER = "{{BIN_COMPOSER}}";
\$DEPLOY_BASE_DIR = "{{DEPLOY_BASE_DIR}}";
\$DEPLOY_SERVER = "{{DEPLOY_SERVER}}";
\$DEPLOY_USER = "{{DEPLOY_USER}}";
\$SSH_PORT = "{{SSH_PORT}}";
PHP
            ),
        ]);

        return $this;
    }
}
