<?php

use Illuminate\Support\Facades\Http;
use EvelynLabs\Braintrust\Scorers\ExactMatch;

// =============================================================================
// Setup
// =============================================================================

beforeEach(function () {
    Http::preventStrayRequests();
    config()->set('braintrust.api_key', 'test-key');
    config()->set('braintrust.base_url', 'https://api.braintrust.dev');
});

afterEach(function () {
    // Cleanup any temp files that might have been left behind
    $tempFiles = glob(sys_get_temp_dir() . '/eval-test-*.php');
    foreach ($tempFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

// Helper function to create temp config files for validation tests (no closures)
function createValidationTestConfig(array $data): string
{
    $tempFile = sys_get_temp_dir() . '/eval-test-' . uniqid() . '.php';

    $phpCode = "<?php\n\nreturn [\n";
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $phpCode .= "    '{$key}' => '" . addslashes($value) . "',\n";
        } elseif (is_array($value)) {
            $phpCode .= "    '{$key}' => " . var_export($value, true) . ",\n";
        }
    }
    $phpCode .= "];";

    file_put_contents($tempFile, $phpCode);
    return $tempFile;
}

// Helper function to create temp config with closures for success tests
function createRunnableConfig(string $experimentName, array $dataset): string
{
    $tempFile = sys_get_temp_dir() . '/eval-test-' . uniqid() . '.php';

    $phpCode = <<<'PHP'
<?php

use EvelynLabs\Braintrust\Scorers\ExactMatch;

return [
    'experiment' => '%s',
    'dataset' => %s,
    'task' => function ($input) {
        return $input;
    },
    'scorers' => [new ExactMatch()],
];
PHP;

    $phpCode = sprintf($phpCode, $experimentName, var_export($dataset, true));
    file_put_contents($tempFile, $phpCode);
    return $tempFile;
}

// =============================================================================
// Validation Failures
// =============================================================================

it('fails when file not found', function () {
    $this->artisan('braintrust:eval', ['path' => '/nonexistent/file.php'])
        ->assertFailed()
        ->expectsOutputToContain('File not found');
});

it('fails when missing required experiment key', function () {
    $tempFile = createValidationTestConfig([
        'dataset' => [],
        'task' => 'dummy',
        'scorers' => [],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->assertFailed()
        ->expectsOutputToContain('Missing required key: experiment');

    unlink($tempFile);
});

it('fails when missing required dataset key', function () {
    $tempFile = createValidationTestConfig([
        'experiment' => 'test-experiment',
        'task' => 'dummy',
        'scorers' => [],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->assertFailed()
        ->expectsOutputToContain('Missing required key: dataset');

    unlink($tempFile);
});

it('fails when missing required task key', function () {
    $tempFile = createValidationTestConfig([
        'experiment' => 'test-experiment',
        'dataset' => [],
        'scorers' => [],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->assertFailed()
        ->expectsOutputToContain('Missing required key: task');

    unlink($tempFile);
});

it('fails when missing required scorers key', function () {
    $tempFile = createValidationTestConfig([
        'experiment' => 'test-experiment',
        'dataset' => [],
        'task' => 'dummy',
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->assertFailed()
        ->expectsOutputToContain('Missing required key: scorers');

    unlink($tempFile);
});

// =============================================================================
// Successful Execution
// =============================================================================

it('runs experiment with valid config', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $tempFile = createRunnableConfig('test-experiment', [
        ['input' => 'hello', 'expected' => 'hello'],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->assertSuccessful();

    unlink($tempFile);
});

it('displays experiment name', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $tempFile = createRunnableConfig('my-awesome-experiment', [
        ['input' => 'hello', 'expected' => 'hello'],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->expectsOutputToContain('Running experiment: my-awesome-experiment');

    unlink($tempFile);
});

it('displays per row scores table', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $tempFile = createRunnableConfig('test-experiment', [
        ['input' => 'hello', 'expected' => 'hello'],
        ['input' => 'world', 'expected' => 'world'],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->expectsOutputToContain('Per-row scores:');

    unlink($tempFile);
});

it('displays aggregate scores table', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $tempFile = createRunnableConfig('test-experiment', [
        ['input' => 'hello', 'expected' => 'hello'],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->expectsOutputToContain('Aggregate scores:');

    unlink($tempFile);
});

// =============================================================================
// Threshold Checking
// =============================================================================

it('passes when all scorers meet thresholds', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    // Set threshold below expected score (all scores will be 1.0 for exact match)
    config()->set('braintrust.thresholds', [
        'exact_match' => 0.5,
    ]);

    $tempFile = createRunnableConfig('test-experiment', [
        ['input' => 'hello', 'expected' => 'hello'],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->assertSuccessful()
        ->expectsOutputToContain('All scorers passed their thresholds!');

    unlink($tempFile);
});

it('fails when scorer below threshold', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    // Set threshold above expected score (all scores will be 0.0 for mismatch)
    config()->set('braintrust.thresholds', [
        'exact_match' => 0.9,
    ]);

    $tempFile = createRunnableConfig('test-experiment', [
        ['input' => 'hello', 'expected' => 'world'], // Mismatch = 0.0 score
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->assertFailed();

    unlink($tempFile);
});

it('displays failed scorers', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    // Set threshold above expected score
    config()->set('braintrust.thresholds', [
        'exact_match' => 0.9,
    ]);

    $tempFile = createRunnableConfig('test-experiment', [
        ['input' => 'hello', 'expected' => 'world'], // Mismatch = 0.0 score
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->expectsOutputToContain('Some scorers failed to meet their thresholds:')
        ->expectsOutputToContain('exact_match: 0.0000 (threshold: 0.9000)');

    unlink($tempFile);
});

// =============================================================================
// Display Formatting
// =============================================================================

it('truncates long input in table', function () {
    Http::fake([
        'https://api.braintrust.dev/v1/experiment' => Http::response(['id' => 'exp-123'], 201),
        'https://api.braintrust.dev/v1/experiment/exp-123/insert' => Http::response([], 200),
    ]);

    $longInput = str_repeat('a', 100); // Create a long input string

    $tempFile = createRunnableConfig('test-experiment', [
        ['input' => $longInput, 'expected' => $longInput],
    ]);

    $this->artisan('braintrust:eval', ['path' => $tempFile])
        ->expectsOutputToContain('...'); // Should contain truncation indicator

    unlink($tempFile);
});
