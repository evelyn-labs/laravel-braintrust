<?php

use EvelynLabs\Braintrust\Experiment;
use EvelynLabs\Braintrust\Http\BraintrustClient;
use EvelynLabs\Braintrust\Scorers\ExactMatch;
use EvelynLabs\Braintrust\Scorers\ScoreContract;
use EvelynLabs\Braintrust\Span;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

// =============================================================================
// Creation Tests
// =============================================================================

it('creates new experiment via api', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response([
            'id' => 'exp-test-123',
        ], 201),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'my-experiment');

    $id = $experiment->createOrResume();

    expect($id)->toBe('exp-test-123');
});

it('includes project id when provided', function () {
    $capturedBody = null;

    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => function ($request) use (&$capturedBody) {
            $capturedBody = $request->data();

            return Http::response(['id' => 'exp-123'], 201);
        },
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'my-experiment', 'my-project');

    $experiment->createOrResume();

    expect($capturedBody)->toHaveKey('project_id')
        ->and($capturedBody['project_id'])->toBe('my-project');
});

it('omits project id when null', function () {
    $capturedBody = null;

    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => function ($request) use (&$capturedBody) {
            $capturedBody = $request->data();

            return Http::response(['id' => 'exp-123'], 201);
        },
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'my-experiment', null);

    $experiment->createOrResume();

    expect($capturedBody)->not->toHaveKey('project_id')
        ->and($capturedBody['name'])->toBe('my-experiment')
        ->and($capturedBody['ensure_new'])->toBeTrue();
});

it('omits project id when empty string', function () {
    $capturedBody = null;

    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => function ($request) use (&$capturedBody) {
            $capturedBody = $request->data();

            return Http::response(['id' => 'exp-123'], 201);
        },
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'my-experiment', '');

    $experiment->createOrResume();

    expect($capturedBody)->not->toHaveKey('project_id');
});

it('stores experiment id after creation', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response([
            'id' => 'stored-exp-id',
        ], 201),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'my-experiment');

    $id = $experiment->createOrResume();

    // Verify the ID was returned
    expect($id)->toBe('stored-exp-id');
});

it('returns experiment id', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response([
            'id' => 'returned-id-456',
        ], 201),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'my-experiment');

    $result = $experiment->createOrResume();

    expect($result)->toBe('returned-id-456')
        ->and($result)->toBeString();
});

it('throws exception when no id returned', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response([
            'message' => 'Created successfully',
            // No 'id' key
        ], 201),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'my-experiment');

    $experiment->createOrResume();
})->throws(RuntimeException::class, 'Failed to create or resume experiment: no ID returned');

// =============================================================================
// Running Experiments Tests
// =============================================================================

it('runs experiment with dataset', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [['input' => 'hello', 'expected' => 'hello']],
        function ($input) {
            return $input;
        },
        [$scorer]
    );

    expect($results)->toHaveKey('spans')
        ->and($results)->toHaveKey('aggregates');
});

it('calls task callable for each row', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $callCount = 0;
    $scorer = new ExactMatch;

    $experiment->run(
        [
            ['input' => 'first', 'expected' => 'first'],
            ['input' => 'second', 'expected' => 'second'],
            ['input' => 'third', 'expected' => 'third'],
        ],
        function ($input) use (&$callCount) {
            $callCount++;

            return $input;
        },
        [$scorer]
    );

    expect($callCount)->toBe(3);
});

it('passes input to task callable', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $receivedInputs = [];
    $scorer = new ExactMatch;

    $experiment->run(
        [
            ['input' => 'input-one', 'expected' => 'expected-one'],
            ['input' => 'input-two', 'expected' => 'expected-two'],
        ],
        function ($input) use (&$receivedInputs) {
            $receivedInputs[] = $input;

            return $input;
        },
        [$scorer]
    );

    expect($receivedInputs)->toBe(['input-one', 'input-two']);
});

it('passes null input when key missing', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $receivedInput = 'not-null';
    $scorer = new ExactMatch;

    $experiment->run(
        [['expected' => 'some-value']], // No 'input' key
        function ($input) use (&$receivedInput) {
            $receivedInput = $input;

            return 'output';
        },
        [$scorer]
    );

    expect($receivedInput)->toBeNull();
});

it('applies scorers to output', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [['input' => 'test', 'expected' => 'test']], // Exact match
        function ($input) {
            return $input; // Returns same as input
        },
        [$scorer]
    );

    expect($results['spans'][0]->scores)->toHaveKey('exact_match')
        ->and($results['spans'][0]->scores['exact_match'])->toBe(1.0);
});

it('creates spans with scores', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [['input' => 'test', 'expected' => 'mismatch']], // No match
        fn ($input) => $input,
        [$scorer]
    );

    $span = $results['spans'][0];

    expect($span->scores)->toBeArray()
        ->and($span->scores)->toHaveCount(1)
        ->and($span->scores['exact_match'])->toBe(0.0);
});

it('creates spans with metrics', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [['input' => 'test', 'expected' => 'test']],
        function ($input) {
            // Add small delay to ensure measurable duration
            usleep(1000); // 1ms delay

            return $input;
        },
        [$scorer]
    );

    $span = $results['spans'][0];

    expect($span->metrics)->toHaveKey('start')
        ->and($span->metrics)->toHaveKey('end')
        ->and($span->metrics)->toHaveKey('duration');

    // Verify duration is calculated (start <= end)
    expect($span->metrics['start'])->toBeLessThanOrEqual($span->metrics['end'])
        ->and($span->metrics['duration'])->toBeGreaterThanOrEqual(0);
});

it('inserts each span to api', function () {
    $insertCallCount = 0;
    $capturedBodies = [];

    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => function ($request) use (&$insertCallCount, &$capturedBodies) {
            $insertCallCount++;
            $capturedBodies[] = $request->data();

            return Http::response([], 200);
        },
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $experiment->run(
        [
            ['input' => 'first', 'expected' => 'first'],
            ['input' => 'second', 'expected' => 'second'],
        ],
        fn ($input) => $input,
        [$scorer]
    );

    expect($insertCallCount)->toBe(2)
        ->and($capturedBodies[0])->toHaveKey('events')
        ->and($capturedBodies[1])->toHaveKey('events');
});

it('returns spans in results', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [
            ['input' => 'first', 'expected' => 'first'],
            ['input' => 'second', 'expected' => 'second'],
            ['input' => 'third', 'expected' => 'third'],
        ],
        fn ($input) => $input,
        [$scorer]
    );

    expect($results['spans'])->toBeArray()
        ->and($results['spans'])->toHaveCount(3)
        ->and($results['spans'][0])->toBeInstanceOf(Span::class);
});

it('returns aggregates in results', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [['input' => 'test', 'expected' => 'test']],
        fn ($input) => $input,
        [$scorer]
    );

    expect($results['aggregates'])->toBeArray()
        ->and($results['aggregates'])->toHaveKey('exact_match');
});

it('calculates correct averages', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    // 2 exact matches (score = 1.0) and 1 mismatch (score = 0.0)
    $results = $experiment->run(
        [
            ['input' => 'match1', 'expected' => 'match1'],
            ['input' => 'match2', 'expected' => 'match2'],
            ['input' => 'nomatch', 'expected' => 'different'],
        ],
        fn ($input) => $input,
        [$scorer]
    );

    // Average: (1.0 + 1.0 + 0.0) / 3 = 0.666666...
    expect($results['aggregates']['exact_match']['avg'])->toBe(2.0 / 3.0);
});

it('counts rows per scorer', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [
            ['input' => 'a', 'expected' => 'a'],
            ['input' => 'b', 'expected' => 'b'],
            ['input' => 'c', 'expected' => 'c'],
            ['input' => 'd', 'expected' => 'd'],
            ['input' => 'e', 'expected' => 'e'],
        ],
        fn ($input) => $input,
        [$scorer]
    );

    expect($results['aggregates']['exact_match']['count'])->toBe(5);
});

it('auto creates experiment if not created', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'auto-created-exp'], 201),
        'https://api.braintrust.dev/v1/experiment/auto-created-exp/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    // Don't call createOrResume first

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [['input' => 'test', 'expected' => 'test']],
        fn ($input) => $input,
        [$scorer]
    );

    expect($results)->toHaveKey('spans')
        ->and($results['spans'])->toHaveCount(1);
});

it('preserves experiment id if already created', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'first-exp-id'], 201),
        'https://api.braintrust.dev/v1/experiment/first-exp-id/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    // Create the experiment first
    $firstId = $experiment->createOrResume();

    $scorer = new ExactMatch;

    $experiment->run(
        [['input' => 'test', 'expected' => 'test']],
        fn ($input) => $input,
        [$scorer]
    );

    // Verify experiment was only created once
    Http::assertSentCount(2); // One for create, one for insert

    // The insert should use the first ID
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'first-exp-id/insert');
    });
});

// =============================================================================
// Edge Cases
// =============================================================================

it('handles empty dataset', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $taskCalled = false;
    $scorer = new ExactMatch;

    $results = $experiment->run(
        [], // Empty dataset
        function ($input) use (&$taskCalled) {
            $taskCalled = true;

            return $input;
        },
        [$scorer]
    );

    expect($taskCalled)->toBeFalse()
        ->and($results['spans'])->toHaveCount(0)
        ->and($results['aggregates'])->toHaveCount(0);
});

it('continues when task returns null', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $results = $experiment->run(
        [
            ['input' => 'test', 'expected' => 'expected'],
        ],
        fn ($input) => null, // Task returns null
        [$scorer]
    );

    // Should not throw exception, should complete with null output
    expect($results['spans'])->toHaveCount(1)
        ->and($results['spans'][0]->output)->toBeNull();
});

// =============================================================================
// Multiple Scorers Tests
// =============================================================================

it('handles multiple scorers', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'test', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    // Create a mock second scorer
    $secondScorer = Mockery::mock(ScoreContract::class);
    $secondScorer->shouldReceive('name')->andReturn('custom_scorer');
    $secondScorer->shouldReceive('score')->andReturn(0.5);

    $firstScorer = new ExactMatch;

    $results = $experiment->run(
        [['input' => 'test', 'expected' => 'test']],
        fn ($input) => $input,
        [$firstScorer, $secondScorer]
    );

    expect($results['spans'][0]->scores)->toHaveCount(2)
        ->and($results['spans'][0]->scores)->toHaveKey('exact_match')
        ->and($results['spans'][0]->scores)->toHaveKey('custom_scorer')
        ->and($results['aggregates'])->toHaveKey('exact_match')
        ->and($results['aggregates'])->toHaveKey('custom_scorer');
});

// =============================================================================
// Request Verification Tests
// =============================================================================

it('sends correct authorization header on experiment creation', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
    ]);

    $client = new BraintrustClient(['api_key' => 'my-secret-key', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $experiment->createOrResume();

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer my-secret-key');
    });
});

it('sends correct authorization header on span insert', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $client = new BraintrustClient(['api_key' => 'my-secret-key', 'base_url' => 'https://api.braintrust.dev']);
    $experiment = new Experiment($client, 'test-exp');

    $scorer = new ExactMatch;

    $experiment->run(
        [['input' => 'test', 'expected' => 'test']],
        fn ($input) => $input,
        [$scorer]
    );

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer my-secret-key')
            && str_contains($request->url(), '/insert');
    });
});
