<?php

use HexideDigital\GitlabDeploy\Helpers\Builders\ConfigurationBuilder;
use HexideDigital\GitlabDeploy\Helpers\ParseConfiguration;

it('parses configuration', function () {
    $parser = new ParseConfiguration();

    $path = dump(config('gitlab-deploy.config-file'));

    $fileData = $parser->parseFile($path);

    dump($fileData);

    expect($fileData)->toBeArray();
});

it('builds', function () {
    $fileData = [];
    $builder = app(ConfigurationBuilder::class);

    $configurations = $builder->build($fileData);
});
