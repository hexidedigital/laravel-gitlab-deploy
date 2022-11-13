<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers\Builders;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Gitlab\GitlabProject;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function collect;

final class GitlabProjectBuilder
{
    /**
     * @param array $gitlab
     * @return array{project: GitlabProject}
     * @throws GitlabDeployException
     */
    public function build(array $gitlab): array
    {
        $project = $gitlab['project'];

        $this->validate($project);

        $project = new GitlabProject(
            id: $project['project-id'],
            token: $project['token'],
            url: $project['domain'],
        );

        return [
            'project' => $project,
        ];
    }

    /**
     * @param array|null $project
     * @return void
     * @throws GitlabDeployException
     */
    public function validate(?array $project): void
    {
        /*todo - validate before create*/
        /** @var Collection<string, bool> $listOfEmptyOptions */
        $listOfEmptyOptions = collect([
            'gitlab' => empty($project),
            'token' => empty(Arr::get($project, 'token', '')),
            'domain' => empty(Arr::get($project, 'domain', '')),
            'projectId' => empty(Arr::get($project, 'project-id', '')),
        ])->filter()->keys();

        if ($listOfEmptyOptions->isNotEmpty()) {
            throw GitlabDeployException::emptyGitlabProjectCredentials($listOfEmptyOptions->all());
        }
    }
}
