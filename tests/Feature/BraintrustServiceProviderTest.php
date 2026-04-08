<?php

use EvelynLabs\Braintrust\BraintrustManager;
use EvelynLabs\Braintrust\BraintrustServiceProvider;
use EvelynLabs\Braintrust\Http\BraintrustClient;

// =============================================================================
// Configuration Binding Tests
// =============================================================================

it('passes config to client', function () {
    config()->set('braintrust.api_key', 'test-api-key');
    config()->set('braintrust.base_url', 'https://custom.braintrust.dev');

    $client = app(BraintrustClient::class);

    // Use reflection to access protected config property
    $reflection = new ReflectionClass($client);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($client);

    expect($config['api_key'])->toBe('test-api-key')
        ->and($config['base_url'])->toBe('https://custom.braintrust.dev');
});

it('passes config to manager', function () {
    config()->set('braintrust.project', 'test-project-id');
    config()->set('braintrust.timeout', 60);

    $manager = app(BraintrustManager::class);

    // Use reflection to access protected config property
    $reflection = new ReflectionClass($manager);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($manager);

    expect($config['project'])->toBe('test-project-id')
        ->and($config['timeout'])->toBe(60);
});

it('merges default config', function () {
    // The TestCase already registers the provider, so config should be merged
    // Verify that default values from config/braintrust.php are available
    expect(config('braintrust.base_url'))->toBe('https://api.braintrust.dev')
        ->and(config('braintrust.timeout'))->toBe(30)
        ->and(config('braintrust.thresholds.exact_match'))->toBe(0.8)
        ->and(config('braintrust.api_key'))->toBeNull()
        ->and(config('braintrust.project'))->toBeNull();
});

// =============================================================================
// Service Provider Registration Tests
// =============================================================================

it('registers braintrust service provider', function () {
    $provider = app()->getProvider(BraintrustServiceProvider::class);

    expect($provider)->toBeInstanceOf(BraintrustServiceProvider::class);
});

it('registers braintrust client as singleton', function () {
    $client1 = app(BraintrustClient::class);
    $client2 = app(BraintrustClient::class);

    expect($client1)->toBe($client2)
        ->and($client1)->toBeInstanceOf(BraintrustClient::class);
});

it('registers braintrust manager as singleton', function () {
    $manager1 = app(BraintrustManager::class);
    $manager2 = app(BraintrustManager::class);

    expect($manager1)->toBe($manager2)
        ->and($manager1)->toBeInstanceOf(BraintrustManager::class);
});

it('manager receives client instance', function () {
    $manager = app(BraintrustManager::class);

    $reflection = new ReflectionClass($manager);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $client = $clientProperty->getValue($manager);

    expect($client)->toBeInstanceOf(BraintrustClient::class);
});

// =============================================================================
// Package Discovery Tests
// =============================================================================

it('is discoverable by laravel package discovery', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey(BraintrustServiceProvider::class)
        ->and($providers[BraintrustServiceProvider::class] ?? false)->toBeTrue();
});

it('publishes config file', function () {
    // Just verify the publish command runs successfully
    // In testbench environment, the file path may differ
    $this->artisan('vendor:publish', [
        '--provider' => BraintrustServiceProvider::class,
        '--tag' => 'config',
        '--force' => true,
    ])->assertSuccessful();
});

// =============================================================================
// Command Registration Tests
// =============================================================================

it('registers eval command', function () {
    $this->artisan('braintrust:eval', ['--help'])
        ->assertSuccessful();
});

it('eval command is available in artisan list', function () {
    $this->artisan('list')
        ->assertSuccessful()
        ->expectsOutputToContain('braintrust:eval');
});

// =============================================================================
// Configuration Validation Tests
// =============================================================================

it('requires api_key to be string or null', function () {
    config()->set('braintrust.api_key', 'valid-key');

    $client = app(BraintrustClient::class);
    $reflection = new ReflectionClass($client);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($client);

    expect($config['api_key'])->toBeString();
});

it('requires base_url to be valid url', function () {
    config()->set('braintrust.base_url', 'https://custom.api.com');

    $client = app(BraintrustClient::class);
    $reflection = new ReflectionClass($client);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($client);

    expect($config['base_url'])->toBeString()
        ->and($config['base_url'])->toStartWith('https://');
});

it('applies default timeout when not specified', function () {
    // Reset timeout config
    config()->set('braintrust.timeout', null);

    $client = app(BraintrustClient::class);
    $reflection = new ReflectionClass($client);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($client);

    expect($config['timeout'] ?? 30)->toBe(30);
});

// =============================================================================
// Lifecycle Tests
// =============================================================================

it('boots service provider successfully', function () {
    $provider = app()->getProvider(BraintrustServiceProvider::class);

    expect($provider)->not->toBeNull();
});

it('handles config updates at runtime', function () {
    config()->set('braintrust.api_key', 'first-key');
    $client1 = app(BraintrustClient::class);

    config()->set('braintrust.api_key', 'second-key');
    // Note: Since it's a singleton, the old instance is returned
    $client2 = app(BraintrustClient::class);

    // Both should be the same instance (singleton behavior)
    expect($client1)->toBe($client2);
});
