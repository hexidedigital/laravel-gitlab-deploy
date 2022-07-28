<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeployOptions;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class DeployParser
{
    public float $version;

    public string $stageName;
    public string $token;
    public string $domain;
    public string $projectId;

    private Options $options;
    private Server $server;
    private Database $database;
    private Mail $mail;

    /** @throws GitlabDeployException */
    public function parseFile(string $filePath, string $stage): void
    {
        $deployYaml = Yaml::parseFile($filePath);

        $this->stageName = $stage;

        $this->parseStageOptions(Arr::get($deployYaml, 'access.' . $stage, []));
        $this->parseGitlabCredentials(Arr::get($deployYaml, 'git-lab', []));
        $this->version = floatval(Arr::get($deployYaml, 'version', 0.1));
    }

    public function parseStageOptions(array $allOptions): void
    {
        if (empty($allOptions)) {
            throw new GitlabDeployException('Accesses for stage "' . $this->stageName . '" not present or empty');
        }

        $this->options = new Options(Arr::get($allOptions, 'options', []));
        $this->server = new Server(Arr::get($allOptions, 'server', []));
        $this->database = new Database(Arr::get($allOptions, 'database', []));
        $this->mail = new Mail(Arr::get($allOptions, 'mail', []));

        $listOfEmptyOptions = $this->getEmptyKeys([
            '`options`' => $this->options->isEmpty(),
            '`server`' => $this->server->isEmpty(),
            '`database`' => $this->database->isEmpty(),
        ]);

        if (!empty($listOfEmptyOptions)) {
            throw new GitlabDeployException('To process deploy prepare you must specify all values. Empty options: ' . implode(', ', $listOfEmptyOptions));
        }
    }

    public function parseGitlabCredentials(array $gitlab)
    {
        $listOfEmptyOptions = $this->getEmptyKeys([
            'gitlab' => empty($gitlab),
            'token' => empty(Arr::get($gitlab, 'token', '')),
            'domain' => empty(Arr::get($gitlab, 'domain', '')),
            'projectId' => empty(Arr::get($gitlab, 'project-id', '')),
        ]);

        if (!empty($listOfEmptyOptions)) {
            throw new GitlabDeployException('To process deploy prepare you must specify gitlab credentials - ' . implode(', ', $listOfEmptyOptions));
        }

        $this->token = Arr::get($gitlab, 'token');
        $this->domain = Arr::get($gitlab, 'domain');
        $this->projectId = Arr::get($gitlab, 'project-id');
    }

    public function hasMail(): bool
    {
        return !$this->mail->isEmpty();
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getMail(): Mail
    {
        return $this->mail;
    }

    private function getEmptyKeys(array $array): array
    {
        return array_keys(
            array_filter(
                $array,
                fn($value) => $value
            )
        );
    }
}
