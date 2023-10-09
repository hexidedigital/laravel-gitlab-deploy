<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\Exceptions\GitlabDeployException;
use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Support\Str;

final class AddGitlabToKnownHostsOnRemoteHost extends BaseTask implements Task
{
    protected string $name = '✔ Add Gitlab to confirmed (known) hosts on remote server';

    /**
     * @throws GitlabDeployException
     */
    public function execute(Pipedata $pipeData): void
    {
        if (!$this->confirmAction('Append Gitlab public key to remote server known_hosts file?')) {
            $this->skipping();

            return;
        }

        $gitlabPublicKey = $this->getGitlabPublicKey();

        if ($this->publicKeyIsStoredOnRemote($gitlabPublicKey)) {
            $this->getLogger()->line('Remote server already know Gitlab pubic key.');

            return;
        }

        $this->getExecutor()->runCommand(
            "ssh {{remoteSshCredentials}} 'echo \"$gitlabPublicKey\" >> ~/.ssh/known_hosts'"
        );
    }

    private function publicKeyIsStoredOnRemote(string $gitlabPublicKeyInfo): bool
    {
        $gitlabPublicKey = $this->getPublicKey($gitlabPublicKeyInfo);

        $storedPublicKeys = '';
        $this->getExecutor()->runCommand(
            "ssh {{remoteSshCredentials}} \"cat ~/.ssh/known_hosts\"",
            function ($type, $buffer) use (&$storedPublicKeys) {
                $storedPublicKeys = $buffer;
            }
        );

        return Str::contains($storedPublicKeys, $gitlabPublicKey);
    }

    /**
     * @throws GitlabDeployException
     */
    private function getGitlabPublicKey(): string
    {
        $scanResult = '';
        $this->getExecutor()->runCommand(
            'ssh-keyscan -t ssh-rsa ' . config('gitlab-deploy.gitlab-server'),
            function ($type, $buffer) use (&$scanResult) {
                $scanResult = trim($buffer);
            }
        );

        if (!Str::containsAll($scanResult, [
            config('gitlab-deploy.gitlab-server'),
            'ssh-rsa',
        ])) {
            $this->getLogger()->line($scanResult, 'error');

            throw new GitlabDeployException('Failed to retrieve public key for Gitlab server.');
        }

        return $scanResult;
    }

    private function getPublicKey(string $publicKeyScanInfo): string
    {
        // get `public key` from "host ssh-rsa AAA...AB3", which can contains comment

        return (string)Str::of($publicKeyScanInfo)->after('ssh-rsa');
    }
}
