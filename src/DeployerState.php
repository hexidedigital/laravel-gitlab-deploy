<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy;

use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;
use HexideDigital\GitlabDeploy\Helpers\Replacements;
use HexideDigital\GitlabDeploy\Helpers\VariableBagBuilder;

final class DeployerState
{
    private Replacements $replacements;
    private Configurations $configurations;
    private Stage $stage;
    private VariableBag $gitlabVariablesBag;

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


    public function setupGitlabVariables(): void
    {
        $builder = new VariableBagBuilder($this->replacements, $this->stage->name);

        $this->gitlabVariablesBag = $builder->build()->getVariableBag();
    }
}
