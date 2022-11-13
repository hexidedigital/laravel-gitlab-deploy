<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

final class Server implements BaseOption
{
    public string $domain;
    public string $host;
    public string $login;
    public string $password;
    public int $sshPort;

    public function __construct(array $source)
    {
        $this->domain = data_get($source, 'domain');
        $this->host = data_get($source, 'host');
        $this->login = data_get($source, 'login');
        $this->password = data_get($source, 'password');
        $this->sshPort = intval(data_get($source, 'ssh-port')) ?: 22;
    }

    public function toArray(): array
    {
        return [
            'sshPort' => $this->sshPort,
            'domain' => $this->domain,
            'host' => $this->host,
            'login' => $this->login,
            'password' => $this->password,
        ];
    }

    public function toReplacesArray(): array
    {
        return [
            '{{USER}}' => $this->login,
            '{{HOST}}' => $this->host,

            '{{SSH_PORT}}' => $this->sshPort,
            '{{DEPLOY_DOMAIN}}' => $this->domain,
            '{{DEPLOY_SERVER}}' => $this->host,
            '{{DEPLOY_USER}}' => $this->login,
            '{{DEPLOY_PASS}}' => $this->password,
        ];
    }
}
