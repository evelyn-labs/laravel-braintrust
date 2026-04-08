<?php

use EvelynLabs\Braintrust\Scorers\LLMScorer;

it('returns default name when not provided', function () {
    $llmCallable = fn ($prompt) => '0.5';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    expect($scorer->name())->toBe('llm_scorer');
});

it('returns custom name when provided', function () {
    $llmCallable = fn ($prompt) => '0.5';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}', 'custom_llm_judge');
    expect($scorer->name())->toBe('custom_llm_judge');
});

it('replaces placeholders in prompt', function () {
    $receivedPrompt = null;
    $llmCallable = function ($prompt) use (&$receivedPrompt) {
        $receivedPrompt = $prompt;

        return '0.5';
    };

    $rubricPrompt = 'Input: {input}, Output: {output}, Expected: {expected}';
    $scorer = new LLMScorer($llmCallable, $rubricPrompt);

    $scorer->score('test input', 'test output', 'test expected');

    expect($receivedPrompt)->toBe('Input: test input, Output: test output, Expected: test expected');
});

it('calls llm callable with formatted prompt', function () {
    $calledWithPrompt = null;
    $llmCallable = function ($prompt) use (&$calledWithPrompt) {
        $calledWithPrompt = $prompt;

        return '0.8';
    };

    $scorer = new LLMScorer($llmCallable, 'Rate: {output}');
    $scorer->score(null, 'some content', null);

    expect($calledWithPrompt)->toBe('Rate: some content');
});

it('parses simple float response', function () {
    $llmCallable = fn ($prompt) => '0.85';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    $result = $scorer->score(null, 'test', null);
    expect($result)->toBe(0.85);
});

it('parses score from text response', function () {
    $llmCallable = fn ($prompt) => 'The score is 0.85';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    $result = $scorer->score(null, 'test', null);
    expect($result)->toBe(0.85);
});

it('parses score from response with prefix', function () {
    $llmCallable = fn ($prompt) => 'Score: 0.85';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    $result = $scorer->score(null, 'test', null);
    expect($result)->toBe(0.85);
});

it('normalizes percentage to decimal', function () {
    $llmCallable = fn ($prompt) => '85';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    $result = $scorer->score(null, 'test', null);
    expect($result)->toBe(0.85);
});

it('clamps score above 1 to 1', function () {
    $llmCallable = fn ($prompt) => '1.5';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    $result = $scorer->score(null, 'test', null);
    expect($result)->toBe(1.0);
});

it('clamps score below 0 to 0', function () {
    $llmCallable = fn ($prompt) => '-0.5';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    $result = $scorer->score(null, 'test', null);
    expect($result)->toBe(0.0);
});

it('returns 0 when no number in response', function () {
    $llmCallable = fn ($prompt) => 'invalid response without numbers';
    $scorer = new LLMScorer($llmCallable, 'Evaluate: {output}');
    $result = $scorer->score(null, 'test', null);
    expect($result)->toBe(0.0);
});

it('stringifies various types for prompt placeholders', function () {
    $receivedPrompts = [];
    $llmCallable = function ($prompt) use (&$receivedPrompts) {
        $receivedPrompts[] = $prompt;

        return '0.5';
    };

    $scorer = new LLMScorer($llmCallable, 'Input: {input}, Output: {output}, Expected: {expected}');

    // Test all types in one comprehensive test
    $scorer->score('hello world', 42, true);
    $scorer->score(null, ['key' => 'value'], false);
    $scorer->score(3.14159, (object) ['name' => 'test'], null);

    expect($receivedPrompts)->toBe([
        'Input: hello world, Output: 42, Expected: true',
        'Input: null, Output: {"key":"value"}, Expected: false',
        'Input: 3.14159, Output: {"name":"test"}, Expected: null',
    ]);
});
