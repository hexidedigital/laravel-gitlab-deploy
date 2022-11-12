<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Helpers;

use HexideDigital\GitlabDeploy\DeploymentOptions\Configurations;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Database;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Mail;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Options;
use HexideDigital\GitlabDeploy\DeploymentOptions\Options\Server;
use HexideDigital\GitlabDeploy\DeploymentOptions\Stage;
use HexideDigital\GitlabDeploy\DeploymentOptions\StageBag;
use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\Gitlab\GitlabProject;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

final class ParseConfiguration
{
    public Configurations $configurations;

    /**
     * @throws GitlabDeployException
     */
    public function parseFile(string $filePath): void
    {
        $configFileOptions = Yaml::parseFile($filePath);

        $version = floatval(Arr::get($configFileOptions, 'version'));
        $gitlab = $this->parseGitlab(Arr::get($configFileOptions, 'git-lab', []));
        $stageBag = $this->parseStages(Arr::get($configFileOptions, 'stages', []));

        $this->configurations = new Configurations(
            $version,
            $gitlab['project'],
            $stageBag,
        );
    }

    /**
     * @throws GitlabDeployException
     */
    public function parseStages(array $stages): StageBag
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

            $listOfEmptyOptions = $this->getEmptyKeys([
                '`options`' => $options->isEmpty(),
                '`server`' => $server->isEmpty(),
                '`database`' => $database->isEmpty(),
            ]);

            if (!empty($listOfEmptyOptions)) {
                $emptyOptions = implode(', ', $listOfEmptyOptions);
                throw new GitlabDeployException(
                    "To process deploy prepare you must specify all values for stage [$name]. Empty options: $emptyOptions"
                );
            }

            $mail = $this->makeMail(Arr::get($stageOptions, 'mail', []));

            $stage = new Stage($name, $options, $server, $database, $mail);

            $stageBag->add($stage);
        }

        return $stageBag;
    }

    /**
     * @throws GitlabDeployException
     */
    public function parseGitlab(array $gitlab): array
    {
        $project = $gitlab['project'];

        $listOfEmptyOptions = $this->getEmptyKeys([
            'gitlab' => empty($project),
            'token' => empty(Arr::get($project, 'token', '')),
            'domain' => empty(Arr::get($project, 'domain', '')),
            'projectId' => empty(Arr::get($project, 'project-id', '')),
        ]);

        if (!empty($listOfEmptyOptions)) {
            throw new GitlabDeployException(
                'To process deploy prepare you must specify gitlab credentials - '.implode(', ', $listOfEmptyOptions)
            );
        }

        $project = new GitlabProject(
            id: $project['project-id'],
            token: $project['token'],
            url: $project['domain'],
        );

        return [
            'project' => $project,
        ];
    }

    private function makeMail(?array $mailOptions): ?Mail
    {
        if (empty($mailOptions)) {
            return null;
        }

        return new Mail($mailOptions);
    }

    private function getEmptyKeys(array $array): array
    {
        return array_keys(
            array_filter(
                $array,
                fn ($value) => $value
            )
        );
    }
}
