<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers\Builders;

use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($array, [
            'version' => ['required', 'numeric'],

            'git-lab' => ['required', 'array'],
            'git-lab.project' => ['required', 'array'],
            'git-lab.project.token' => ['required', 'string'],
            'git-lab.project.project-id' => ['required', 'string'],
            'git-lab.project.domain' => ['required', 'string'],

            'stages' => ['required', 'array'],
            'stages.*.name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw GitlabDeployException::validationErrors($validator->errors()->keys());
        }

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
