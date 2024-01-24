<?php

use function PHPUnit\Framework\{assertEquals, assertFalse, assertNotEquals, assertTrue};

beforeEach(function () {
    safeUnlink(config_path('gitlab-deploy.php'));
});

afterEach(function () {
    safeUnlink(config_path('gitlab-deploy.php'));
});

test('the install command copies the configuration', function () {
    // make sure we're starting from a clean state
    assertFalse(File::isFile(config_path('gitlab-deploy.php')));

    $this->artisan('deploy:install')
        ->assertSuccessful();

    assertTrue(File::isFile(config_path('gitlab-deploy.php')));
});

test('when a config file is present users can choose to not overwrite it', function () {
    File::put(config_path('gitlab-deploy.php'), 'initial content');
    assertTrue(File::isFile(config_path('gitlab-deploy.php')));

    $command = $this->artisan('deploy:install')
        ->expectsQuestion(
            question: 'Config file already exists. Do you want to overwrite it?',
            answer: false
        );

    $command->execute();

    $command
        ->doesntExpectOutput('Overwriting configuration file...')
        ->expectsOutput('Existing configuration was not overwritten')
        ->assertSuccessful();

    assertEquals('initial content', File::get(config_path('gitlab-deploy.php')));
});

test('when a config file is present users can choose to overwrite it', function () {
    File::put(config_path('gitlab-deploy.php'), 'initial content');
    assertTrue(File::isFile(config_path('gitlab-deploy.php')));

    $command = $this->artisan('deploy:install')
        ->expectsQuestion(
            question: 'Config file already exists. Do you want to overwrite it?',
            answer: true
        );

    $command->execute();

    $command
        ->doesntExpectOutput('Existing configuration was not overwritten')
        ->expectsOutput('Overwriting configuration file...')
        ->assertSuccessful();

    assertNotEquals('initial content', File::get(config_path('gitlab-deploy.php')));
    assertEquals(
        File::get(__DIR__ . '/../../../config/gitlab-deploy.php'),
        File::get(config_path('gitlab-deploy.php'))
    );
});

test('the install command copies sample files', function (string $sampleFile) {
    safeUnlink(config_path('gitlab-deploy.php'));

    $sampleFile = base_path($sampleFile);

    safeUnlink($sampleFile);

    assertFalse(File::isFile($sampleFile));

    $this->artisan('deploy:install', ['-q' => true, '-n' => true])
        ->execute();

    assertTrue(File::isFile($sampleFile));
})->with([
    '.deploy/.gitignore',
    '.deploy/deploy-prepare.yml',
    'deploy.php',
]);

test('adds ssh and work folders to root .gitignore file', function () {
    File::put(base_path('.gitignore'), implode(PHP_EOL, ["/vendor", "/.idea"]));

    config([
        'gitlab-deploy.config-file' => base_path('.deploy/deploy-prepare.yml'),
        'gitlab-deploy.ssh.folder' => base_path('.ssh/{{STAGE}}'),
    ]);

    $command = $this->artisan('deploy:install')
        ->assertSuccessful();

    $command->execute();

    $command
        ->expectsOutput('Updating: .gitignore file')
        ->expectsOutput('Updated: .gitignore file');

    assertEquals(
        implode(PHP_EOL, ["/vendor", "/.idea", "/.ssh", "/.deploy"]),
        File::get(base_path('.gitignore'))
    );

    safeUnlink(base_path('.gitignore'));
});

test('do not edit when ssh and work folders already in root .gitignore file', function () {
    File::put(base_path('.gitignore'), implode(PHP_EOL, ["/vendor", "/.idea", "/.ssh", "/.deploy"]));

    config([
        'gitlab-deploy.config-file' => base_path('.deploy/deploy-prepare.yml'),
        'gitlab-deploy.ssh.folder' => base_path('.ssh/{{STAGE}}'),
    ]);

    $command = $this->artisan('deploy:install')
        ->assertSuccessful();

    $command->execute();

    $command
        ->expectsOutput('Updating: .gitignore file')
        ->expectsOutput('No need to update.')
        ->doesntExpectOutput('Updated: .gitignore file');

    assertEquals(
        implode(PHP_EOL, ["/vendor", "/.idea", "/.ssh", "/.deploy"]),
        File::get(base_path('.gitignore'))
    );

    safeUnlink(base_path('.gitignore'));
});

test('do nothing when in project missing .gitignore file', function () {
    safeUnlink(base_path('.gitignore'));
    assertFalse(File::isFile(base_path('.gitignore')));

    $command = $this->artisan('deploy:install')
        ->assertSuccessful();

    $command->execute();

    $command->doesntExpectOutput('Updating: .gitignore file');

    assertFalse(File::isFile(base_path('.gitignore')));
});
