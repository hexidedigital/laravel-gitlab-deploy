<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Tasks\GitlabVariablesCreator;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;
use HexideDigital\GitlabDeploy\PipeData;

final class CreateProjectVariablesOnGitlab extends BaseTask implements Task
{
    protected string $name = 'Gitlab variables';

    public function __construct(
        private readonly GitlabVariablesCreator $creator,
    ) {
    }

    public function execute(Pipedata $pipeData): void
    {
        $variableBag = $this->getState()->getGitlabVariablesBag();

        $this->printVariables($variableBag);

        if (!$this->confirmAction('Update Gitlab variables?')) {
            return;
        }

        $this->getLogger()->appendEchoLine('Connecting to Gitlab and creating variables...');

        $this->creator
            ->setProject($this->getState()->getConfigurations()->project)
            ->setVariableBag($variableBag);

        $this->creator->execute();

        $this->printMessages();
    }

    /**
     * Variables that have a large content to display in a table
     *
     * @return array<string>
     */
    private function printAloneKeys(): array
    {
        return [
            'SSH_PRIVATE_KEY',
            'SSH_PUB_KEY',
        ];
    }

    private function printVariables(VariableBag $variableBag): void
    {
        foreach ($variableBag->only($this->printAloneKeys()) as $variable) {
            $this->getLogger()->appendEchoLine($variable->key, 'comment');
            $this->getLogger()->appendEchoLine($variable->value);
        }

        $rows = [];
        foreach ($variableBag->except($this->printAloneKeys()) as $variable) {
            $this->getLogger()->writeToFile($variable->key . PHP_EOL . $variable->value . PHP_EOL);

            $rows[] = [$variable->key, $variable->value];
        }

        if (isset($this->command)) {
            $this->getCommand()->table(['key', 'value'], $rows);
        }

        $this->getLogger()->appendEchoLine('<comment>tip</comment>: put `SSH_PUB_KEY` to path => <info>Gitlab.project -> Settings -> Repository -> Deploy keys</info>');
    }

    private function printMessages(): void
    {
        foreach ($this->creator->getMessages() as $message) {
            $this->getLogger()->appendEchoLine($message, 'comment');
        }

        $fails = $this->creator->getFailMassages();

        $count = sizeof($fails);

        $this->getLogger()->appendEchoLine('Gitlab variables created with "<info>' . $count . '</info>" fail messages');

        foreach ($fails as $failMessage) {
            $this->getLogger()->appendEchoLine($failMessage, 'error');
        }
    }
}
