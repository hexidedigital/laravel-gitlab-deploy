<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

final class Mail implements BaseOption
{
    public readonly string $name;
    public readonly string $login;
    public readonly string $password;

    public function __construct(array $source)
    {
        $this->name = data_get($source, 'hostname');
        $this->login = data_get($source, 'username');
        $this->password = data_get($source, 'password');
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'login' => $this->login,
            'password' => $this->password,
        ];
    }

    public function toReplacesArray(): array
    {
        return [
            'MAIL_HOSTNAME' => $this->name,
            'MAIL_USER' => $this->login,
            'MAIL_PASSWORD' => $this->password,
        ];
    }
}
