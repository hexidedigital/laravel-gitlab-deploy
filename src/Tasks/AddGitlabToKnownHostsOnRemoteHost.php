<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\Support\Str;

final class AddGitlabToKnownHostsOnRemoteHost extends BaseTask implements Task
{
    protected string $name = '✔ Add Gitlab to confirmed (known hosts) on remote host';

    public function execute(Pipedata $pipeData): void
    {
        if (!$this->confirmAction('Append Gitlab IP to remote host known_hosts file?')) {
            $this->skipping();

            return;
        }

        $knownHost = '';
        $this->getExecutor()->runCommand(
            'ssh-keyscan -t ecdsa-sha2-nistp256 ' . config('gitlab-deploy.gitlab-server'),
            function ($type, $buffer) use (&$knownHost) {
                $knownHost = trim($buffer);
            }
        );

        $sshRemote = 'ssh {{remoteSshCredentials}}';

        $remoteKnownHosts = '';
        $this->getExecutor()->runCommand(
            $sshRemote . ' "cat ~/.ssh/known_hosts"',
            function ($type, $buffer) use (&$remoteKnownHosts) {
                $remoteKnownHosts = $buffer;
            }
        );

        if (!Str::contains($remoteKnownHosts, $knownHost)) {
            $this->getExecutor()->runCommand($sshRemote . " 'echo \"$knownHost\" >> ~/.ssh/known_hosts'");
        } else {
            $this->getLogger()->appendEchoLine('Remote server already know Gitlab host.');
        }
    }
}
