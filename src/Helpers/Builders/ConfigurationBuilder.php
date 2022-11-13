<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers\Builders;

use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use Illuminate\Support\Arr;

final class ConfigurationBuilder
{
    public function __construct(
        private readonly StageBagBuilder $stageBagBuilder,
        private readonly GitlabProjectBuilder $gitlabProjectBuilder,
    ) {
    }

    /**
     * @param array $array
     * @return Configurations
     * @throws GitlabDeployException
     */
    public function build(array $array): Configurations
    {
        $version = floatval(Arr::get($array, 'version'));

        $gitlab = $this->gitlabProjectBuilder->build(Arr::get($array, 'git-lab', []));
        $stageBag = $this->stageBagBuilder->build(Arr::get($array, 'stages', []));

        return new Configurations(
            $version,
            $gitlab['project'],
            $stageBag,
        );
    }
}
