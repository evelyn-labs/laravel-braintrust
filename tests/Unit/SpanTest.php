<?php

use EvelynLabs\Braintrust\Span;

it('generates uuid when id not provided', function () {
    $span = new Span;
    expect($span->id)->toBeString()->not->toBeEmpty();
    // Should be a valid UUID format
    expect($span->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('sets all attributes from array', function () {
    $attributes = [
        'id' => 'test-id',
        'input' => 'test input',
        'output' => 'test output',
        'expected' => 'expected output',
        'scores' => ['accuracy' => 0.95, 'relevance' => 0.87],
        'metadata' => ['source' => 'test', 'version' => '1.0'],
        'metrics' => ['latency' => 123.45, 'tokens' => 150],
    ];

    $span = new Span($attributes);

    expect($span->id)->toBe('test-id');
    expect($span->input)->toBe('test input');
    expect($span->output)->toBe('test output');
    expect($span->expected)->toBe('expected output');
    expect($span->scores)->toBe(['accuracy' => 0.95, 'relevance' => 0.87]);
    expect($span->metadata)->toBe(['source' => 'test', 'version' => '1.0']);
    expect($span->metrics)->toBe(['latency' => 123.45, 'tokens' => 150]);
});

it('converts to array with all properties', function () {
    $span = new Span([
        'id' => 'test-id',
        'input' => 'test input',
        'output' => 'test output',
        'expected' => 'expected output',
    ]);

    $array = $span->toArray();

    expect($array)->toHaveKeys(['id', 'input', 'output', 'expected']);
    expect($array['id'])->toBe('test-id');
    expect($array['input'])->toBe('test input');
    expect($array['output'])->toBe('test output');
    expect($array['expected'])->toBe('expected output');
});

it('omits empty scores from array', function () {
    $span = new Span([
        'id' => 'test-id',
        'input' => 'test',
    ]);

    $array = $span->toArray();

    expect($array)->not->toHaveKey('scores');
});

it('omits empty metadata from array', function () {
    $span = new Span([
        'id' => 'test-id',
        'input' => 'test',
    ]);

    $array = $span->toArray();

    expect($array)->not->toHaveKey('metadata');
});

it('omits empty metrics from array', function () {
    $span = new Span([
        'id' => 'test-id',
        'input' => 'test',
    ]);

    $array = $span->toArray();

    expect($array)->not->toHaveKey('metrics');
});

it('includes scores when not empty', function () {
    $span = new Span([
        'id' => 'test-id',
        'scores' => ['accuracy' => 0.95],
    ]);

    $array = $span->toArray();

    expect($array)->toHaveKey('scores');
    expect($array['scores'])->toBe(['accuracy' => 0.95]);
});

it('includes metadata when not empty', function () {
    $span = new Span([
        'id' => 'test-id',
        'metadata' => ['source' => 'test'],
    ]);

    $array = $span->toArray();

    expect($array)->toHaveKey('metadata');
    expect($array['metadata'])->toBe(['source' => 'test']);
});

it('includes metrics when not empty', function () {
    $span = new Span([
        'id' => 'test-id',
        'metrics' => ['latency' => 100.0],
    ]);

    $array = $span->toArray();

    expect($array)->toHaveKey('metrics');
    expect($array['metrics'])->toBe(['latency' => 100.0]);
});
