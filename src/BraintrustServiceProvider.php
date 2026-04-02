<?php

namespace EvelynLabs\Braintrust;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use EvelynLabs\Braintrust\Console\EvalCommand;
use EvelynLabs\Braintrust\Http\BraintrustClient;

class BraintrustServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-braintrust')
            ->hasConfigFile()
            ->hasCommand(EvalCommand::class);
    }

    public function register(): void
    {
        parent::register();

        // Bind BraintrustClient as singleton
        $this->app->singleton(BraintrustClient::class, function ($app) {
            return new BraintrustClient(
                $app['config']['braintrust']
            );
        });

        // Bind BraintrustManager as singleton
        $this->app->singleton(BraintrustManager::class, function ($app) {
            return new BraintrustManager(
                $app->make(BraintrustClient::class),
                $app['config']['braintrust']
            );
        });
    }
}
