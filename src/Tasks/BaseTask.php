<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\DeployerState;
use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Helpers\BasicLogger;
use HexideDigital\GitlabDeploy\Helpers\Replacements;

abstract class BaseTask implements Task
{
    protected readonly Configurations $configurations;
    protected readonly Replacements $replacements;
    protected readonly Stage $stage;
    protected readonly BasicLogger $logger;
    protected readonly DeployerState $state;

    public function setState(DeployerState $state): void
    {
        $this->state = $state;
        $this->replacements = $state->getReplacements();
        $this->configurations = $state->getConfigurations();
        $this->stage = $state->getStage();
    }

    public function setLogger(BasicLogger $logger): void
    {
        $this->logger = $logger;
    }

    public function getTaskName(): string
    {
        return $this->name;
    }

    public function shouldRunInPrintMode(): bool
    {
        return true;
    }

    public function canBeSkipped(): bool
    {
        return true;
    }

    public function execute(): void
    {
        $content = $this->replacements->replace($this->getContent());

        $this->logger->appendEchoLine($content);
    }

    private function getContent(): string
    {
        return "
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
    password: {{DB_PASSWORD}}";
    }
}
