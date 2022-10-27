<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;
use HexideDigital\GitlabDeploy\Helpers\ParseConfiguration;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\Helpers\ReplacementsBuilder;
use HexideDigital\GitlabDeploy\Helpers\VariableBagBuilder;
use function str;

final class DeployerState
{
    private Replacements $replacements;
    private Configurations $configurations;
    private Stage $stage;
    private VariableBag $gitlabVariablesBag;

    /**
     * @throws GitlabDeployException
     */
    public function prepare(string $getStageName): void
    {
        $this->parseConfigurations($getStageName);
        $this->setupReplacements();
        $this->setupGitlabVariables();
    }

    /**
     * @throws GitlabDeployException
     */
    public function parseConfigurations(string $stageName): void
    {
        $parser = app(ParseConfiguration::class);

        $parser->parseFile(config('gitlab-deploy.config-file'));

        $this->setConfigurations($parser->configurations);
        $this->setStage($parser->configurations->stageBag->get($stageName));
    }

    public function setupGitlabVariables(): void
    {
        $builder = new VariableBagBuilder($this->replacements, $this->stage->name);

        $this->gitlabVariablesBag = $builder->build()->getVariableBag();
    }

    public function setupReplacements(): void
    {
        $builder = new ReplacementsBuilder($this->getStage());

        $replacements = $builder->build()->getReplacements();

        $this->setReplacements($replacements);
    }

    public function getReplacements(): Replacements
    {
        return $this->replacements;
    }

    public function setReplacements(Replacements $replacements): void
    {
        $this->replacements = $replacements;
    }

    public function getConfigurations(): Configurations
    {
        return $this->configurations;
    }

    public function setConfigurations(Configurations $configurations): void
    {
        $this->configurations = $configurations;
    }

    public function getStage(): Stage
    {
        return $this->stage;
    }

    public function setStage(Stage $stage): void
    {
        $this->stage = $stage;
    }

    public function getGitlabVariablesBag(): VariableBag
    {
        return $this->gitlabVariablesBag;
    }

    public function setGitlabVariablesBag(VariableBag $gitlabVariablesBag): void
    {
        $this->gitlabVariablesBag = $gitlabVariablesBag;
    }
}
