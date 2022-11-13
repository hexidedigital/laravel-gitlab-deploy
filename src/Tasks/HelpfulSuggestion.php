<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

final class HelpfulSuggestion extends BaseTask implements Task
{
    protected string $name = 'IDEA Setup and helpers';

    public function handle(): void
    {
        $content = $this->replacements->replace($this->getContent());

        $this->logger->appendEchoLine($content);
    }

    private function getContent(): string
    {
        return <<<EOF
    <info>- mount path</info>
    {{DEPLOY_BASE_DIR}}

    <info>- site url</info>
    {{DEPLOY_SERVER}}

    <info>- add mapping for deployment</info>
    /current

    <info>- configure crontab / schedule</info>
    crontab -e

    * * * * * cd {{DEPLOY_BASE_DIR}}/current && {{BIN_PHP}} artisan schedule:run >> /dev/null 2>&1

    <info>- connect to databases (local and remote)</info>
    port: {{SSH_PORT}}
    domain: {{DEPLOY_DOMAIN}}
    db_name: {{DB_DATABASE}}
    db_user: {{DB_USERNAME}}
    password: {{DB_PASSWORD}}
EOF;
    }
}
