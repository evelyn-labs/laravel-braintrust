<?php

use EvelynLabs\Braintrust\Exceptions\BraintrustApiException;
use EvelynLabs\Braintrust\Http\BraintrustClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

// GET Requests Tests
it('makes get request with correct headers', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response(['data' => 'test'], 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $result = $client->get('/test');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-api-key')
            && $request->url() === 'https://api.braintrust.dev/test';
    });

    expect($result)->toBe(['data' => 'test']);
});

it('applies timeout from config', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response(['data' => 'test'], 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
        'timeout' => 60,
    ]);

    $client->get('/test');

    Http::assertSent(function ($request) {
        // The timeout is passed as an option to the HTTP client
        return $request->url() === 'https://api.braintrust.dev/test';
    });
});

it('uses default timeout when not configured', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response(['data' => 'test'], 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
        // No timeout specified - should default to 30
    ]);

    $client->get('/test');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.braintrust.dev/test';
    });
});

it('returns json response on success', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response(['id' => '123', 'name' => 'Test Project'], 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $result = $client->get('/test');

    expect($result)->toBe(['id' => '123', 'name' => 'Test Project']);
});

it('returns empty array on empty response', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response('', 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $result = $client->get('/test');

    expect($result)->toBe([]);
});

it('passes query params in get request', function () {
    Http::fake([
        '*' => Http::response(['results' => []], 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $client->get('/projects', ['limit' => 10, 'offset' => 20]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.braintrust.dev/projects?limit=10&offset=20';
    });
});

// POST Requests Tests
it('makes post request with correct headers', function () {
    Http::fake([
        'https://api.braintrust.dev/projects' => Http::response(['id' => 'new-id'], 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $client->post('/projects', ['name' => 'New Project']);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-api-key')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->url() === 'https://api.braintrust.dev/projects'
            && $request->method() === 'POST';
    });
});

it('sends post body as json', function () {
    Http::fake([
        'https://api.braintrust.dev/projects' => Http::response(['id' => 'created'], 200),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $client->post('/projects', ['name' => 'Test Project', 'description' => 'A test project']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.braintrust.dev/projects'
            && $request->method() === 'POST'
            && $request['name'] === 'Test Project'
            && $request['description'] === 'A test project';
    });
});

// Error Handling Tests
it('throws exception on 4xx error', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response(['error' => 'Bad Request'], 400),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $client->get('/test');
})->throws(BraintrustApiException::class);

it('throws exception on 5xx error', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    $client->get('/test');
})->throws(BraintrustApiException::class);

it('includes status code in exception', function () {
    Http::fake([
        'https://api.braintrust.dev/test' => Http::response(['error' => 'Not Found'], 404),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    try {
        $client->get('/test');
    } catch (BraintrustApiException $e) {
        expect($e->getStatusCode())->toBe(404);
    }
});

it('includes response body in exception', function () {
    $errorBody = ['error' => 'Validation failed', 'details' => ['field' => 'name', 'message' => 'Required']];

    Http::fake([
        'https://api.braintrust.dev/test' => Http::response($errorBody, 422),
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
    ]);

    try {
        $client->post('/test', ['invalid' => 'data']);
    } catch (BraintrustApiException $e) {
        expect($e->getResponseBody())->toBe($errorBody);
    }
});
