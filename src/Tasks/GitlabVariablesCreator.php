<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use Gitlab;
use GrahamCampbell\GitLab\GitLabManager;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GitlabVariablesCreator
{
    private const SEPARATOR = '%';

    /** @var GitLabManager|Gitlab\Client */
    private $client;

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
        string $token,
        string $url,
        string $projectId,
        string $scope
    )
    {
        $this->token = $token;
        $this->url = $url;
        $this->projectId = $projectId;
        $this->envScope = $scope;
    }

    public function setCurrentProjectVariables(array $variableMap)
    {
        $this->variablesMap = $variableMap;
    }

    /** @throws GitlabDeployException */
    public function execute(): ?array
    {
        $this->prepareClient();

        $this->projects = $this->client->projects();

        $this->currentProjectVariables = $this->getProjectVariables();

        $this->processVariables();
        $this->setDeployKeys();

        return [
            $this->fails,
            $this->messages,
        ];
    }

    /** @throws GitlabDeployException */
    protected function prepareClient()
    {
        /** @var Gitlab\Client $client */
        $client = app(GitLabManager::class);

        if (empty($this->token)) {
            throw new GitlabDeployException('Provide api token for gitlab');
        }

        if (empty($this->url)) {
            throw new GitlabDeployException('Provide domain url for gitlab');
        }

        $client->setUrl($this->url);
        $client->authenticate($this->token, Gitlab\Client::AUTH_HTTP_TOKEN);

        $this->client = $client;
    }

    protected function processVariables(): void
    {
        foreach (Arr::except($this->variablesMap, ['SSH_PUB_KEY']) as $key => $value) {
            try {
                $this->createOrUpdateVariable($key, $value);
            } catch (\Exception $exception) {
                $this->fails[] = 'Failed to create variable [' . $key . '].'
                    . ' Exception message [' . $exception->getMessage() . '].'
                    . ' Exception class [' . get_class($exception) . ']';
            }
        }
    }

    protected function setDeployKeys(): void
    {
        $pubKey = Arr::get($this->variablesMap, 'SSH_PUB_KEY');

        // get `user@host` from "ssh-rsa AAA...AB3 user@host"
        $title = Str::of($pubKey)->explode(' ')->last();

        try {
            $this->client->projects()->addDeployKey(
                $this->projectId,
                $title,
                $pubKey,
                false
            );
        } catch (\Exception $exception) {
            $this->fails[] = 'Failed to append deploy key.'
                . ' Exception message [' . $exception->getMessage() . '].'
                . ' Exception class [' . get_class($exception) . ']';
        }
    }

    public function createOrUpdateVariable(string $key, ?string $value)
    {
        $value = !is_null($value) ? $value : '';

        if ($this->isVariablePresent($key)) {
            return $this->updateVariable($key, $value);
        }

        return $this->createVariable($key, $value);
    }

    private function createVariable(string $key, string $value)
    {
        return $this->projects->addVariable(
            $this->projectId, $key, $value, false, $this->envScope, [
                'filter' => ['environment_scope' => $this->envScope],
            ]
        );
    }

    private function updateVariable(string $key, string $value)
    {
        return $this->projects->updateVariable(
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

        return $key . self::SEPARATOR . $envScope;
    }
}
