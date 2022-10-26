<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeployOptions;

final class Database extends OptionsContainer
{
    public readonly string $name;
    public readonly string $login;
    public readonly string $password;

    public function makeFromSource(array $source): void
    {
        $this->name = data_get($source, 'database');
        $this->login = data_get($source, 'username');
        $this->password = data_get($source, 'password');
    }

    public function toArray(): array
    {
        return [
            '{{DB_DATABASE}}' => $this->name,
            '{{DB_USERNAME}}' => $this->login,
            '{{DB_PASSWORD}}' => $this->password,
        ];
    }
}
