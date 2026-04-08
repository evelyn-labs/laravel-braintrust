<?php

namespace EvelynLabs\Braintrust;

use EvelynLabs\Braintrust\Console\EvalCommand;
use EvelynLabs\Braintrust\Http\BraintrustClient;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasCommand(EvalCommand::class)
            ->hasInstallCommand(function ($command) {
                $command
                    ->publishConfigFile();
            });
    }

    public function packageRegistered(): void
    {
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
