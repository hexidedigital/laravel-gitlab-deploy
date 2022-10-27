<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Gitlab\Tasks\GitlabVariablesCreator;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;

final class CreateProjectVariablesOnGitlab extends BaseTask implements Task
{
    protected string $name = 'gitlab variables';

    public function __construct(
        private readonly GitlabVariablesCreator $creator,
    )
    {
    }

    public function execute(): void
    {
        $variableBag = $this->state->getGitlabVariablesBag();

        $this->printVariables($variableBag);

        if (!$this->confirmAction('Update gitlab variables?')) {
            return;
        }

        $this->logger->appendEchoLine('Connecting to gitlab and creating variables...');

        $this->creator
            ->setProject($this->state->getConfigurations()->project)
            ->setVariableBag($variableBag);

        $this->creator->execute();

        $this->printMessages();
    }

    private function printVariables(VariableBag $variableBag)
    {
        foreach ($variableBag->only($variableBag->printAloneKeys()) as $variable) {
            $this->logger->appendEchoLine($variable->key, 'comment');
            $this->logger->appendEchoLine($variable->value);
        }

        $rows = [];
        foreach ($variableBag->except($variableBag->printAloneKeys()) as $variable) {
            $this->logger->writeToFile($variable->key.PHP_EOL.$variable->value.PHP_EOL);

            $rows[] = [$variable->key, $variable->value];
        }

        if (isset($this->command)) {

            $this->command->table(['key', 'value'], $rows);
        }

        $this->logger->appendEchoLine(
            "tip: put `SSH_PUB_KEY` to path => Gitlab.project -> Settings -> Repository -> Deploy keys",
            'comment'
        );
    }

    private function printMessages(): void
    {
        foreach ($this->creator->getMessages() as $message) {
            $this->logger->appendEchoLine($message, 'comment');
        }

        $fails = $this->creator->getFailMassages();

        $this->logger->appendEchoLine('Gitlab variables created with "'.sizeof($fails).'" fail messages');

        foreach ($fails as $fail) {
            $this->logger->appendEchoLine($fail, 'error');
        }
    }
}
