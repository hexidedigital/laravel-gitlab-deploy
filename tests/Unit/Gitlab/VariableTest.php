<?php

use HexideDigital\GitlabDeploy\Gitlab\Variable;

it('store all properties correctly', function () {
    $variable = new Variable('SSH_PORT', 'dev', '22');

    expect($variable)
        ->key->toBe('SSH_PORT')
        ->scope->toBe('dev')
        ->value->toBe('22');
});

it('has `value` as string if given value', function ($value, $expected) {
    $variable = new Variable('SSH_PORT', 'dev', $value);

    expect($variable->value)
        ->toBeString()
        ->toBe($expected);
})->with([
    [null, ''],
    [false, ''],
    [true, '1'],
    [22, '22'],
    ['', ''],
    ['22', '22'],
]);
