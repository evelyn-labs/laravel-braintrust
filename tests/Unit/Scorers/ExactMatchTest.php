<?php

use EvelynLabs\Braintrust\Scorers\ExactMatch;

it('returns correct name', function () {
    $scorer = new ExactMatch();
    expect($scorer->name())->toBe('exact_match');
});

it('returns 1 for exact string match', function () {
    $scorer = new ExactMatch();
    $result = $scorer->score('some input', 'hello world', 'hello world');
    expect($result)->toBe(1.0);
});

it('returns 0 for string mismatch', function () {
    $scorer = new ExactMatch();
    $result = $scorer->score('some input', 'hello world', 'goodbye world');
    expect($result)->toBe(0.0);
});

it('returns 1 for exact integer match', function () {
    $scorer = new ExactMatch();
    $result = $scorer->score('some input', 42, 42);
    expect($result)->toBe(1.0);
});

it('returns 0 for type mismatch', function () {
    $scorer = new ExactMatch();
    $result = $scorer->score('some input', '42', 42);
    expect($result)->toBe(0.0);
});

it('returns 1 for exact array match', function () {
    $scorer = new ExactMatch();
    $array = ['key' => 'value', 'list' => [1, 2, 3]];
    $result = $scorer->score('some input', $array, $array);
    expect($result)->toBe(1.0);
});

it('returns 1 for null match', function () {
    $scorer = new ExactMatch();
    $result = $scorer->score('some input', null, null);
    expect($result)->toBe(1.0);
});

it('returns 1 for boolean true match', function () {
    $scorer = new ExactMatch();
    $result = $scorer->score('some input', true, true);
    expect($result)->toBe(1.0);
});

it('returns 0 for boolean mismatch', function () {
    $scorer = new ExactMatch();
    $result = $scorer->score('some input', true, false);
    expect($result)->toBe(0.0);
});

it('ignores input parameter', function () {
    $scorer = new ExactMatch();

    // The input parameter should not affect the score
    $result1 = $scorer->score('input A', 'output', 'output');
    $result2 = $scorer->score(['key' => 'value'], 'output', 'output');
    $result3 = $scorer->score(null, 'output', 'output');
    $result4 = $scorer->score(123, 'output', 'output');

    expect($result1)->toBe(1.0);
    expect($result2)->toBe(1.0);
    expect($result3)->toBe(1.0);
    expect($result4)->toBe(1.0);
});
