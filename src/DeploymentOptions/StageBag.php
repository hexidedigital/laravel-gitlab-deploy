<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;

final class StageBag
{
    /**
     * All the registered stages
     *
     * @var array<string, Stage>
     */
    private array $stages;

    /**
     * Add a new stage to stages bag
     *
     * @param Stage $stage
     * @return $this
     */
    public function add(Stage $stage): StageBag
    {
        $this->stages[$stage->name] = $stage;

        return $this;
    }

    /**
     * Get a stage from the stages bag for a given name.
     *
     * @param string $name
     * @return Stage
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
