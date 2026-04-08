<?php

use Illuminate\Support\Facades\Http;
use EvelynLabs\Braintrust\Http\BraintrustClient;
use EvelynLabs\Braintrust\Dataset;

beforeEach(function () {
    Http::preventStrayRequests();
});

// =============================================================================
// Fetch Operations
// =============================================================================

it('fetches rows from api', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/dataset/test-dataset/fetch' => Http::response([
            'rows' => [
                ['input' => 'test', 'expected' => 'result'],
                ['input' => 'another', 'expected' => 'output'],
            ]
        ], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'test-dataset');

    $rows = $dataset->fetch();

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toBe(['input' => 'test', 'expected' => 'result'])
        ->and($rows[1])->toBe(['input' => 'another', 'expected' => 'output']);
});

it('returns empty array when no rows', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/dataset/empty-dataset/fetch' => Http::response([
            'rows' => []
        ], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'empty-dataset');

    $rows = $dataset->fetch();

    expect($rows)->toBeArray()
        ->and($rows)->toHaveCount(0);
});

it('passes dataset id to api path', function () {
    $capturedUrl = null;

    Http::fake([
        '*' => function ($request) use (&$capturedUrl) {
            $capturedUrl = $request->url();
            return Http::response(['rows' => []], 200);
        }
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'my-specific-dataset-id');

    $dataset->fetch();

    expect($capturedUrl)->toBe('https://api.braintrust.dev/v1/dataset/my-specific-dataset-id/fetch');
});

// =============================================================================
// Insert Operations
// =============================================================================

it('inserts rows via api', function () {
    $capturedBody = null;

    Http::fake([
        'https://api.braintrust.dev/v1/dataset/test-dataset/insert' => function ($request) use (&$capturedBody) {
            $capturedBody = $request->data();
            return Http::response([], 200);
        }
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'test-dataset');

    $rows = [
        ['input' => 'first', 'expected' => 'result1'],
        ['input' => 'second', 'expected' => 'result2'],
    ];

    $dataset->insert($rows);

    expect($capturedBody)->toBe([
        'rows' => $rows
    ]);
});

it('handles empty rows insert', function () {
    $apiCalled = false;
    $capturedBody = null;

    Http::fake([
        'https://api.braintrust.dev/v1/dataset/test-dataset/insert' => function ($request) use (&$apiCalled, &$capturedBody) {
            $apiCalled = true;
            $capturedBody = $request->data();
            return Http::response([], 200);
        }
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'test-dataset');

    $dataset->insert([]);

    expect($apiCalled)->toBeTrue()
        ->and($capturedBody)->toBe(['rows' => []]);
});

it('includes correct dataset id in insert url', function () {
    $capturedUrl = null;

    Http::fake([
        '*' => function ($request) use (&$capturedUrl) {
            $capturedUrl = $request->url();
            return Http::response([], 200);
        }
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'insert-dataset-123');

    $dataset->insert([['input' => 'test']]);

    expect($capturedUrl)->toBe('https://api.braintrust.dev/v1/dataset/insert-dataset-123/insert');
});

it('makes post request with json content type', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/dataset/test-dataset/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'test-dataset');

    $dataset->insert([['input' => 'test']]);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Content-Type', 'application/json')
            && $request->method() === 'POST';
    });
});

it('sends authorization header on fetch', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/dataset/test-dataset/fetch' => Http::response([
            'rows' => []
        ], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'my-api-key', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'test-dataset');

    $dataset->fetch();

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer my-api-key');
    });
});

it('sends authorization header on insert', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/dataset/test-dataset/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'my-api-key', 'base_url' => 'https://api.braintrust.dev']);
    $dataset = new Dataset($client, 'test-dataset');

    $dataset->insert([['input' => 'test']]);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer my-api-key');
    });
});
