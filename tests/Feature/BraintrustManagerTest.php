<?php

use EvelynLabs\Braintrust\BraintrustManager;
use EvelynLabs\Braintrust\Http\BraintrustClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

// =============================================================================
// Experiment Instance Tests
// =============================================================================

it('passes project from config to experiment', function () {
    $client = new BraintrustClient([
        'api_key' => 'test',
        'base_url' => 'https://api.braintrust.dev',
    ]);
    $manager = new BraintrustManager($client, ['project' => 'my-project']);

    $experiment = $manager->experiment('test-exp');

    // Verify project was passed by checking the experiment creates with project
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
    ]);

    $experiment->createOrResume();

    Http::assertSent(function ($request) {
        return $request->data()['project_id'] === 'my-project';
    });
});

it('passes null project when not in config', function () {
    $client = new BraintrustClient([
        'api_key' => 'test',
        'base_url' => 'https://api.braintrust.dev',
    ]);
    $manager = new BraintrustManager($client, []); // No project in config

    $experiment = $manager->experiment('test-exp');

    // Verify no project was passed by checking the experiment creates without project
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
    ]);

    $experiment->createOrResume();

    Http::assertSent(function ($request) {
        return ! isset($request->data()['project_id']);
    });
});

// =============================================================================
// Dataset Instance Tests
// =============================================================================

it('passes dataset id correctly', function () {
    $capturedUrl = null;

    Http::fake([
        '*' => function ($request) use (&$capturedUrl) {
            $capturedUrl = $request->url();

            return Http::response(['rows' => []], 200);
        },
    ]);

    $client = new BraintrustClient([
        'api_key' => 'test',
        'base_url' => 'https://api.braintrust.dev',
    ]);
    $manager = new BraintrustManager($client, ['project' => 'my-project']);

    $dataset = $manager->dataset('my-specific-dataset-id');
    $dataset->fetch();

    expect($capturedUrl)->toBe('https://api.braintrust.dev/v1/dataset/my-specific-dataset-id/fetch');
});

// =============================================================================
// Client Singleton Tests
// =============================================================================

it('uses same client for all instances', function () {
    $client = new BraintrustClient([
        'api_key' => 'test',
        'base_url' => 'https://api.braintrust.dev',
    ]);
    $manager = new BraintrustManager($client, ['project' => 'my-project']);

    // Create both an experiment and a dataset
    $experiment = $manager->experiment('test-exp');
    $dataset = $manager->dataset('test-dataset');

    // Setup HTTP fake for both endpoints
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/dataset/test-dataset/fetch' => Http::response(['rows' => []], 200),
    ]);

    // Use both instances - they should both use the same client
    $experiment->createOrResume();
    $dataset->fetch();

    // Both requests should have the same authorization header from the shared client
    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test');
    });
});
