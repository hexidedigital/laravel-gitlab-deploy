<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeploymentOptions\Options;

final class Database implements BaseOption
{
    public readonly string $name;
    public readonly string $login;
    public readonly string $password;

    public function __construct(array $source)
    {
        $this->name = data_get($source, 'database');
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
            'DB_DATABASE' => $this->name,
            'DB_USERNAME' => $this->login,
            'DB_PASSWORD' => $this->password,
        ];
    }

    public function allowEmptyValueForArrayKeys(): array
    {
        return [];
    }
}
