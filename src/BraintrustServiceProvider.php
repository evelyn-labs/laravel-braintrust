<?php

namespace EvelynLabs\Braintrust;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use EvelynLabs\Braintrust\Commands\BraintrustCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel_braintrust_table')
            ->hasCommand(BraintrustCommand::class);
    }
}
