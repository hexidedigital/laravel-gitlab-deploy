<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeployOptions;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class DeployParser
{
    public string $stageName;
    public string $token;
    public string $domain;
    /** @var string|int */
    public $projectId;

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

        if (
            $this->options->isEmpty()
            || $this->server->isEmpty()
            || $this->database->isEmpty()
        ) {
            throw new GitlabDeployException('To process deploy prepare you must specify all values for `options`, `server` and `database` options for access');
        }
    }

    public function parseGitlabCredentials(array $gitlab)
    {
        if (
            empty($gitlab)
            || !($this->token = Arr::get($gitlab, 'token', ''))
            || !($this->domain = Arr::get($gitlab, 'domain', ''))
            || !($this->projectId = Arr::get($gitlab, 'project-id', ''))
        ) {
            throw new GitlabDeployException('To process deploy prepare you must specify gitlab credentials - `token`, `domain` and `project-id` options for access');
        }
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
}
