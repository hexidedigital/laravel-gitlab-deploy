<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use Gitlab;
use GrahamCampbell\GitLab\GitLabManager;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class GitlabVariablesCreator
{
    private const SEPARATOR = '%';

    private Gitlab\Client|GitLabManager $client;

    private string $projectId;
    private string $token;
    private string $url;
    private string $envScope;
    private array $variablesMap;
    private Collection $currentProjectVariables;
    private Gitlab\Api\Projects $projects;

    private array $fails = [];
    private array $messages = [];

    public function __construct(
        GitLabManager $client,
    )
    {
        $this->client = $client;
    }

    public function setProjectId(string $projectId): GitlabVariablesCreator
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function setToken(string $token): GitlabVariablesCreator
    {
        $this->token = $token;

        return $this;
    }

    public function setUrl(string $url): GitlabVariablesCreator
    {
        $this->url = $url;

        return $this;
    }

    public function setEnvScope(string $envScope): GitlabVariablesCreator
    {
        $this->envScope = $envScope;

        return $this;
    }

    public function setCurrentProjectVariables(array $variableMap): GitlabVariablesCreator
    {
        $this->variablesMap = $variableMap;

        return $this;
    }

    /** @throws GitlabDeployException */
    public function execute(): void
    {
        $this->prepareClient();

        $this->projects = $this->client->projects();

        $this->currentProjectVariables = $this->getProjectVariables();

        $this->processVariables();
        $this->setDeployKeys();
    }

    public function getFails(): array
    {
        return $this->fails;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    /** @throws GitlabDeployException */
    private function prepareClient(): void
    {
        if (empty($this->token)) {
            throw new GitlabDeployException('Provide api token for gitlab');
        }

        if (empty($this->url)) {
            throw new GitlabDeployException('Provide domain url for gitlab');
        }

        $this->client->setUrl($this->url);
        $this->client->authenticate($this->token, Gitlab\Client::AUTH_HTTP_TOKEN);
    }

    private function processVariables(): void
    {
        foreach (Arr::except($this->variablesMap, ['SSH_PUB_KEY']) as $key => $value) {
            try {
                $this->createOrUpdateVariable($key, $value);
            } catch (\Exception $exception) {
                $this->fails[] = 'Failed to create variable ['.$key.'].'
                    .' Exception message ['.$exception->getMessage().'].'
                    .' Exception class ['.get_class($exception).']';
            }
        }
    }

    private function setDeployKeys(): void
    {
        $publicKey = Arr::get($this->variablesMap, 'SSH_PUB_KEY');

        try {
            $this->client->projects()->addDeployKey(
                $this->projectId,
                $this->getServerNameFromPublicKey($publicKey),
                $publicKey,
                false
            );
        } catch (\Exception $exception) {
            $this->fails[] = 'Failed to append deploy key.'
                .' Exception message ['.$exception->getMessage().'].'
                .' Exception class ['.get_class($exception).']';
        }
    }

    private function getServerNameFromPublicKey(string $publicKey): string
    {
        // get `user@host` from "ssh-rsa AAA...AB3 user@host"

        return Str::of($publicKey)->explode(' ')->last();
    }

    private function createOrUpdateVariable(string $key, ?string $value): void
    {
        $value = !is_null($value) ? $value : '';

        if ($this->isVariablePresent($key)) {
            $this->updateVariable($key, $value);

            return;
        }

        $this->createVariable($key, $value);
    }

    private function createVariable(string $key, string $value): void
    {
        $this->projects->addVariable(
            $this->projectId, $key, $value, false, $this->envScope, [
                'filter' => ['environment_scope' => $this->envScope],
            ]
        );
    }

    private function updateVariable(string $key, string $value): void
    {
        $this->projects->updateVariable(
            $this->projectId, $key, $value, false, $this->envScope, [
                'filter' => ['environment_scope' => $this->envScope],
            ]
        );
    }

    private function isVariablePresent(string $key): bool
    {
        return $this->currentProjectVariables->has($this->makeKey($key));
    }

    private function getProjectVariables(): Collection
    {
        return collect($this->projects->variables($this->projectId))
            ->mapWithKeys(fn(array $variable) => [
                $this->makeKey($variable['key'], $variable['environment_scope']) => $variable,
            ]);
    }

    private function makeKey(string $key, string $envScope = null): string
    {
        $envScope = $envScope ?: $this->envScope;

        return $key.self::SEPARATOR.$envScope;
    }
}
