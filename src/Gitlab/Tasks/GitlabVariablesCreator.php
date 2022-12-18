<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Gitlab\Tasks;

use Gitlab;
use GrahamCampbell\GitLab\GitLabManager;
use HexideDigital\GitlabDeploy\Gitlab\GitlabProject;
use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class GitlabVariablesCreator
{
    private const SEPARATOR = '%';

    private Gitlab\Client|GitLabManager $gitLabManager;

    private GitlabProject $project;

    private VariableBag $variableBag;

    /**
     * @var Collection<string, array>
     */
    private Collection $projectVariables;

    private Gitlab\Api\Projects $projectsApi;

    private array $failMassages = [];
    private array $messages = [];

    public function __construct(
        GitLabManager $gitLabManager,
    ) {
        $this->gitLabManager = $gitLabManager;
    }

    public function setProject(GitlabProject $project): GitlabVariablesCreator
    {
        $this->project = $project;

        return $this;
    }

    public function setVariableBag(VariableBag $variableBag): GitlabVariablesCreator
    {
        $this->variableBag = $variableBag;

        return $this;
    }

    public function execute(): void
    {
        $this->prepareClient();

        $this->projectsApi = $this->gitLabManager->projects();

        $this->projectVariables = $this->getProjectVariables();

        $this->processVariables();
        $this->setDeployKeys();
    }

    public function getFailMassages(): array
    {
        return $this->failMassages;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    private function prepareClient(): void
    {
        $this->gitLabManager->setUrl($this->project->url);
        $this->gitLabManager->authenticate($this->project->token, Gitlab\Client::AUTH_HTTP_TOKEN);
    }

    private function processVariables(): void
    {
        foreach ($this->variableBag->except(['SSH_PUB_KEY']) as $variable) {
            try {
                $this->createOrUpdateVariable($variable);
            } catch (\Exception $exception) {
                $this->failMassages[] = 'Failed to create variable [' . $variable->key . '].'
                    . ' Exception message [' . $exception->getMessage() . '].'
                    . ' Exception class [' . get_class($exception) . ']';
            }
        }
    }

    private function setDeployKeys(): void
    {
        try {
            $publicKeyVariable = $this->variableBag->get('SSH_PUB_KEY');

            $this->gitLabManager->projects()->addDeployKey(
                project_id: $this->project->id,
                title: $this->getServerNameFromPublicKey($publicKeyVariable->value),
                key: $publicKeyVariable->value,
                canPush: false
            );
        } catch (\Exception $exception) {
            $this->failMassages[] = 'Failed to append deploy key.'
                . ' Exception message [' . $exception->getMessage() . '].'
                . ' Exception class [' . get_class($exception) . ']';
        }
    }

    private function getServerNameFromPublicKey(string $publicKey): string
    {
        // get `user@host` from "ssh-rsa AAA...AB3 user@host"

        return (string)Str::of($publicKey)->explode(' ')->last();
    }

    private function createOrUpdateVariable(Variable $variable): void
    {
        if ($this->isVariablePresent($variable)) {
            $this->updateVariable($variable);

            return;
        }

        $this->createVariable($variable);
    }

    private function createVariable(Variable $variable): void
    {
        $this->projectsApi->addVariable(
            $this->project->id,
            $variable->key,
            $variable->value,
            false,
            $variable->scope,
            ['filter' => ['environment_scope' => $variable->scope]]
        );
    }

    private function updateVariable(Variable $variable): void
    {
        $this->projectsApi->updateVariable(
            $this->project->id,
            $variable->key,
            $variable->value,
            false,
            $variable->scope,
            ['filter' => ['environment_scope' => $variable->scope]]
        );
    }

    private function isVariablePresent(Variable $variable): bool
    {
        return $this->projectVariables->has($this->makeKey($variable->key, $variable->scope));
    }

    /**
     * Returns all stored variables in project for all environments
     *
     * @return Collection<string, array>
     */
    private function getProjectVariables(): Collection
    {
        /** @var array<int, array> $variables */
        $variables = $this->projectsApi->variables($this->project->id);

        return collect($variables)
            ->mapWithKeys(fn (array $variable): array => [
                $this->makeKey($variable['key'], $variable['environment_scope']) => $variable,
            ]);
    }

    private function makeKey(string $key, string $envScope): string
    {
        return $key . self::SEPARATOR . $envScope;
    }
}
