<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;

final class VariableBagBuilder
{
    private VariableBag $variableBag;

    public function __construct(
        private readonly Replacements $replacements,
        private readonly string $stageName,
    )
    {
    }

    public function getVariableBag(): VariableBag
    {
        return $this->variableBag;
    }

    public function build(): VariableBagBuilder
    {
        $bag = new VariableBag();

        $variables = [
            'BIN_PHP' => $this->replacements->replace('{{BIN_PHP}}'),
            'BIN_COMPOSER' => $this->replacements->replace('{{BIN_COMPOSER}}'),

            'DEPLOY_BASE_DIR' => $this->replacements->replace('{{DEPLOY_BASE_DIR}}'),
            'DEPLOY_SERVER' => $this->replacements->replace('{{DEPLOY_SERVER}}'),
            'DEPLOY_USER' => $this->replacements->replace('{{DEPLOY_USER}}'),
            'SSH_PORT' => $this->replacements->replace('{{SSH_PORT}}'),

            'SSH_PRIVATE_KEY' => '-----BEGIN OPENSSH PRIVATE ',
            'SSH_PUB_KEY' => 'rsa-ssh AAA....AAA user@host',

            'CI_ENABLED' => '0',
        ];

        foreach ($variables as $key => $value) {
            $variable = new Variable(
                key: $key,
                scope: $this->stageName,
                value: $value,
            );

            $bag->add($variable->key, $variable);
        }

        $this->variableBag = $bag;

        return $this;
    }
}
