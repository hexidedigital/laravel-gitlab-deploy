<?php

use function PHPUnit\Framework\{assertEquals, assertFalse, assertNotEquals, assertTrue};

function safeUnlink(string $path): void
{
    if (File::isFile($path)) {
        unlink($path);
    }
}

test('the install command copies the configuration', function () {
    // make sure we're starting from a clean state
    safeUnlink(config_path('gitlab-deploy.php'));
    assertFalse(File::isFile(config_path('gitlab-deploy.php')));

    $this->artisan('gitlab-deploy:install')
        ->assertSuccessful();

    assertTrue(File::isFile(config_path('gitlab-deploy.php')));
});

test('when a config file is present users can choose to not overwrite it', function () {
    File::put(config_path('gitlab-deploy.php'), 'initial content');
    assertTrue(File::isFile(config_path('gitlab-deploy.php')));

    $command = $this->artisan('gitlab-deploy:install')
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

    safeUnlink(config_path('gitlab-deploy.php'));
});

test('when a config file is present users can choose to overwrite it', function () {
    File::put(config_path('gitlab-deploy.php'), 'initial content');
    assertTrue(File::isFile(config_path('gitlab-deploy.php')));

    $command = $this->artisan('gitlab-deploy:install')
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
        File::get(__DIR__ . '/../../config/gitlab-deploy.php'),
        File::get(config_path('gitlab-deploy.php'))
    );

    safeUnlink(config_path('gitlab-deploy.php'));
});

test('the install command copies sample files', function (string $sampleFile) {
    safeUnlink(config_path('gitlab-deploy.php'));

    $sampleFile = base_path($sampleFile);

    safeUnlink($sampleFile);

    assertFalse(File::isFile($sampleFile));

    $this->artisan('gitlab-deploy:install', ['-q' => true, '-n' => true])
        ->execute();

    assertTrue(File::isFile($sampleFile));
})->with([
    '.deploy/.gitignore',
    '.deploy/deploy-prepare.yml',
    'deploy.php',
]);
