<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Gitlab;

use Illuminate\Support\Arr;

final class VariableBag
{
    /**
     * @param array<string, Variable> $variables
     */
    public function __construct(
        protected array $variables = [],
    )
    {
    }

    /**
     * @param string $key
     * @param Variable $variable
     * @return self
     */
    public function add(string $key, Variable $variable): self
    {
        $this->variables[$key] = $variable;

        return $this;
    }

    /**
     * @param string $key
     * @return Variable|null
     */
    public function get(string $key): ?Variable
    {
        return Arr::get($this->variables, $key);
    }

    /**
     * @return array<string, Variable>
     */
    public function except(array $keys): array
    {
        return Arr::except($this->variables, $keys);
    }

    /**
     * @return array<string, Variable>
     */
    public function only(array $keys): array
    {
        return Arr::except($this->variables, $keys);
    }

    /**
     * @return array<string>
     */
    public function printAloneKeys(): array
    {
        return [
            'SSH_PRIVATE_KEY',
            'SSH_PUB_KEY',
        ];
    }
}
