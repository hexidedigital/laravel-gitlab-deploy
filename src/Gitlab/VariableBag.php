<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Gitlab;

use Illuminate\Support\Arr;

final class VariableBag
{
    /**
     * All the registered variables
     *
     * @var array<string, Variable>
     */
    protected array $variables = [];

    /**
     * Add a new variable to variables bag
     *
     * @param Variable $variable
     * @return self
     */
    public function add(Variable $variable): self
    {
        $this->variables[$variable->key] = $variable;

        return $this;
    }

    /**
     * Get a variable from the variables bag for a given key.
     *
     * @param string $key
     * @return Variable|null
     */
    public function get(string $key): ?Variable
    {
        return Arr::get($this->variables, $key);
    }

    /**
     * Returns only the variables from the bag with the specified keys.
     *
     * @return array<string, Variable>
     */
    public function except(array $keys): array
    {
        return Arr::except($this->variables, $keys);
    }

    /**
     * Returns all variables in the bag except the variables with specified keys.
     *
     * @return array<string, Variable>
     */
    public function only(array $keys): array
    {
        return Arr::only($this->variables, $keys);
    }
}
