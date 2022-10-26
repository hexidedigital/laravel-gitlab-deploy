<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;

final class StageBag
{
    /**
     * @var array<string, Stage> $stages
     */
    public array $stages = [];

    public function __construct(array $stages = [])
    {
        $this->stages = $stages;
    }

    public function add(Stage $stage): StageBag
    {
        $this->stages[$stage->name] = $stage;

        return $this;
    }

    /**
     * @throws GitlabDeployException
     */
    public function get(string $name): Stage
    {
        if (!isset($this->stages[$name])) {
            throw new GitlabDeployException("Stage [$name] is not defined");
        }

        return $this->stages[$name];
    }
}
