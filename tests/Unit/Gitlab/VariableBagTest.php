<?php

declare(strict_types=1);

use HexideDigital\GitlabDeploy\Gitlab\Variable;
use HexideDigital\GitlabDeploy\Gitlab\VariableBag;

use function PHPUnit\Framework\{assertEquals, assertNull, assertSame};

it('returns same variable from bag', function () {
    $variable = new Variable('SSH_PORT', 'dev', '22');

    $variableBag = new VariableBag();

    $variableBag->add($variable);

    assertSame($variable, $variableBag->get($variable->key));
});

it('returns `null` if variable not stored', function () {
    $variableBag = new VariableBag();

    assertNull($variableBag->get('missing value'));
});

it('returns only variables with specified keys', function () {
    $keys = [
        'key1',
        'key2',
        'key3',
    ];

    $returnOnlyKeys = [
        'key1',
        'key3',
    ];

    $variableBag = new VariableBag();

    foreach ($keys as $key) {
        $variableBag->add(new Variable($key, 'dev', $key));
    }

    assertEquals($returnOnlyKeys, array_keys($variableBag->only($returnOnlyKeys)));
});

it('returns all variables except with specified keys', function () {
    $keys = [
        'key1',
        'key2',
        'key3',
    ];

    $presentKeys = [
        'key2',
    ];

    $skipKeys = [
        'key1',
        'key3',
    ];

    $variableBag = new VariableBag();

    foreach ($keys as $key) {
        $variableBag->add(new Variable($key, 'dev', $key));
    }

    assertEquals($presentKeys, array_keys($variableBag->except($skipKeys)));
});
