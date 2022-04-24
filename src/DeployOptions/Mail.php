<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\DeployOptions;

class Mail extends OptionsContainer
{
    public ?string $name;
    public ?string $login;
    public ?string $password;

    public function makeFromSource(array $source)
    {
        $this->name = data_get($source, 'hostname');
        $this->login = data_get($source, 'username');
        $this->password = data_get($source, 'password');
    }

    public function toArray(): array
    {
        return [
            '{{MAIL_HOSTNAME}}' => $this->name,
            '{{MAIL_USER}}' => $this->login,
            '{{MAIL_PASSWORD}}' => $this->password,
        ];
    }
}
