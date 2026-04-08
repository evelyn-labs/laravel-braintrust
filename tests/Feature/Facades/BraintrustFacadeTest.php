<?php

use EvelynLabs\Braintrust\Facades\Braintrust;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    // Set up config for the service provider
    config()->set('braintrust', [
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.braintrust.dev',
        'project' => 'test-project',
        'timeout' => 30,
    ]);
});

it('creates experiment with correct name via facade', function () {
    $capturedBody = null;

    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => function ($request) use (&$capturedBody) {
            $capturedBody = $request->data();

            return Http::response(['id' => 'exp-123'], 201);
        },
    ]);

    $experiment = Braintrust::experiment('facade-test-experiment');
    $experiment->createOrResume();

    expect($capturedBody['name'])->toBe('facade-test-experiment');
});

it('passes project from config to experiment via facade', function () {
    $capturedBody = null;

    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => function ($request) use (&$capturedBody) {
            $capturedBody = $request->data();

            return Http::response(['id' => 'exp-123'], 201);
        },
    ]);

    $experiment = Braintrust::experiment('test-exp');
    $experiment->createOrResume();

    expect($capturedBody['project_id'])->toBe('test-project');
});

it('creates dataset with correct id via facade', function () {
    $capturedUrl = null;

    Http::fake([
        '*' => function ($request) use (&$capturedUrl) {
            $capturedUrl = $request->url();

            return Http::response(['rows' => []], 200);
        },
    ]);

    $dataset = Braintrust::dataset('facade-test-dataset');
    $dataset->fetch();

    expect($capturedUrl)->toBe('https://api.braintrust.dev/v1/dataset/facade-test-dataset/fetch');
});
