<?php

declare(strict_types=1);

namespace HexideDigital\GitlabDeploy\Tasks;

use HexideDigital\GitlabDeploy\PipeData;
use Illuminate\View\Compilers\BladeCompiler;

final class HelpfulSuggestion extends BaseTask implements Task
{
    protected string $name = '✨ IDEA Setup and helpers';

    public function __construct(
        private readonly BladeCompiler $bladeCompiler
    ) {
    }

    public function execute(Pipedata $pipeData): void
    {
        $content = $this->getReplacements()->replace($this->getTemplateForFile());

        $this->getLogger()->getFileLogger()->line(strip_tags($content));

        $this->getLogger()->getConsoleLogger()->line(
            $this->getReplacements()->replace(
                $this->getTemplateForConsole()
            )
        );
    }

    private function getTemplateForFile(): string
    {
        return <<<EOF
    - Mount path
    {{DEPLOY_BASE_DIR}}

    - Site url
    {{DEPLOY_SERVER}}

    - Add mapping for deployment
    /current

    - Configure crontab / schedule
    crontab -e
    * * * * * cd {{DEPLOY_BASE_DIR}}/current && {{BIN_PHP}} artisan schedule:run >> /dev/null 2>&1

    - Connect to databases
    port: {{SSH_PORT}}
    domain: {{DEPLOY_DOMAIN}}
    db_name: {{DB_DATABASE}}
    db_user: {{DB_USERNAME}}
    password: {{DB_PASSWORD}}
EOF;
    }

    private function getTemplateForConsole(): string
    {
        return $this->bladeCompiler->render(
            /** @lang Blade */
            <<<BLADE
<div class="mb-2">
    <div class="">
        <p class="text-blue-500 font-bold">Mount path</p>
        <span>@{{DEPLOY_BASE_DIR}}</span>
    </div>

    <div class="">
        <p class="text-blue-500 font-bold">Site url</p>
        <span>@{{DEPLOY_SERVER}}</span>
    </div>

    <div class="">
        <p class="text-blue-500 font-bold">Add mapping for deployment</p>
        <span>/current</span>
    </div>
    <div class="">
        <p class="text-blue-500 font-bold">Configure crontab / schedule</p>
        <div class="">
            <div class="mb-1">
                <div class="italic">To open crontab editor, execute:</div>
                <div class="text-green-500">crontab -e</div>
            </div>
            <div class="italic">and write: </div>
            <div class="text-green-500">* * * * * cd @{{DEPLOY_BASE_DIR}}/current && @{{BIN_PHP}} artisan schedule:run >> /dev/null 2>&1</div>
        </div>
    </div>

    <div class="">
        <p class="text-blue-500 font-bold">Connect to databases</p>
        <div class="">
            <div>port: @{{SSH_PORT}}</div>
            <div>domain: @{{DEPLOY_DOMAIN}}</div>
            <div>db_name: @{{DB_DATABASE}}</div>
            <div>db_user: @{{DB_USERNAME}}</div>
            <div>password: @include('gitlab-deploy::console.password', ['password' => '@{{DB_PASSWORD}}'])</div>
        </div>
    </div>
</div>
BLADE
        );
    }
}
