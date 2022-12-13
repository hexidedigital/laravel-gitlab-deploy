<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers\Builders;

use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Database;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Mail;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Options;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Server;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\DeploymentOptions\StageBag;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Helpers\OptionValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class StageBagBuilder
{
    /**
     * @param array $stages
     * @return StageBag
     * @throws GitlabDeployException
     */
    public function build(array $stages): StageBag
    {
        if (empty($stages)) {
            throw new GitlabDeployException('No one stages are defined');
        }

        $stageBag = new StageBag();

        foreach ($stages as $stageOptions) {
            $name = $stageOptions['name'];
            $options = new Options(Arr::get($stageOptions, 'options', []));
            $server = new Server(Arr::get($stageOptions, 'server', []));
            $database = new Database(Arr::get($stageOptions, 'database', []));

            $this->validate($options, $server, $database, $name);

            $mail = $this->makeMail(Arr::get($stageOptions, 'mail', []));

            $stage = new Stage($name, $options, $server, $database, $mail);

            $stageBag->add($stage);
        }

        return $stageBag;
    }

    /**
     * @param Options $options
     * @param Server $server
     * @param Database $database
     * @param string $name
     * @return void
     * @throws GitlabDeployException
     */
    private function validate(Options $options, Server $server, Database $database, string $name): void
    {
        /*todo - validate before create*/
        /** @var Collection<string, bool> $listOfEmptyOptions */
        $listOfEmptyOptions = collect([
            'options' => OptionValidator::onyOfKeyIsEmpty($options),
            'server' => OptionValidator::onyOfKeyIsEmpty($server),
            'database' => OptionValidator::onyOfKeyIsEmpty($database),
        ])
            ->filter()
            ->keys();

        if ($listOfEmptyOptions->isNotEmpty()) {
            throw GitlabDeployException::hasEmptyStageOptions($name, $listOfEmptyOptions->all());
        }
    }

    private function makeMail(?array $mailOptions): ?Mail
    {
        if (empty($mailOptions)) {
            return null;
        }

        return new Mail($mailOptions);
    }
}
