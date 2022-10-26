<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

class Server extends BaseOption
{
    public string $domain;
    public string $host;
    public string $login;
    public string $password;
    public int $sshPort;

    public function make(array $source): void
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
