<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use Illuminate\Support\Str;

final class AddGitlabToKnownHostsOnRemoteHost extends BaseTask implements Task
{
    protected string $name = 'add gitlab to confirmed (known hosts) on remote host';

    public function handle(): void
    {
        if (!$this->confirmAction('Append gitlab IP to remote host known_hosts file?')) {
            return;
        }

        $knownHost = '';
        $this->executor->runCommand(
            'ssh-keyscan -t ecdsa-sha2-nistp256 '.config('gitlab-deploy.gitlab-server'),
            function ($type, $buffer) use (&$knownHost) {
                $knownHost = trim($buffer);
            }
        );

        $sshRemote = 'ssh {{remoteSshCredentials}}';

        $remoteKnownHosts = '';
        $this->executor->runCommand(
            $sshRemote.' "cat ~/.ssh/known_hosts"',
            function ($type, $buffer) use (&$remoteKnownHosts) {
                $remoteKnownHosts = $buffer;
            }
        );

        if (!Str::contains($remoteKnownHosts, $knownHost)) {
            $this->executor->runCommand($sshRemote." 'echo \"$knownHost\" >> ~/.ssh/known_hosts'");
        } else {
            $this->logger->appendEchoLine('Remote server already know gitlab host.');
        }
    }
}
