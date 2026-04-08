<?php

use EvelynLabs\Braintrust\Scorers\Contains;

it('returns correct name', function () {
    $scorer = new Contains;
    expect($scorer->name())->toBe('contains');
});

it('returns 1 when output contains expected', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', 'The quick brown fox jumps over the lazy dog', 'brown fox');
    expect($result)->toBe(1.0);
});

it('returns 0 when output does not contain expected', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', 'The quick brown fox jumps over the lazy dog', 'purple elephant');
    expect($result)->toBe(0.0);
});

it('returns 1 for case sensitive match', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', 'Hello World', 'Hello');
    expect($result)->toBe(1.0);
});

it('returns 0 for case mismatch', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', 'Hello World', 'hello');
    expect($result)->toBe(0.0);
});

it('returns 0 when output is not string', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', 12345, 'test');
    expect($result)->toBe(0.0);
});

it('returns 0 when expected is not string', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', 'test string', 12345);
    expect($result)->toBe(0.0);
});

it('returns 0 when both are not strings', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', ['array'], ['expected']);
    expect($result)->toBe(0.0);
});

it('returns 1 for empty expected in any output', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', 'any string', '');
    expect($result)->toBe(1.0);
});

it('returns 0 for empty output with non empty expected', function () {
    $scorer = new Contains;
    $result = $scorer->score('some input', '', 'something');
    expect($result)->toBe(0.0);
});

it('ignores input parameter', function () {
    $scorer = new Contains;

    // The input parameter should not affect the score
    $result1 = $scorer->score('input A', 'output', 'out');
    $result2 = $scorer->score(['key' => 'value'], 'output', 'out');
    $result3 = $scorer->score(null, 'output', 'out');
    $result4 = $scorer->score(123, 'output', 'out');

    expect($result1)->toBe(1.0);
    expect($result2)->toBe(1.0);
    expect($result3)->toBe(1.0);
    expect($result4)->toBe(1.0);
});
