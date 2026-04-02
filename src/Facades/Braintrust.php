<?php

namespace EvelynLabs\Braintrust\Facades;

use EvelynLabs\Braintrust\BraintrustManager;
use EvelynLabs\Braintrust\Dataset;
use EvelynLabs\Braintrust\Experiment;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Experiment experiment(string $name)
 * @method static Dataset dataset(string $id)
 *
 * @see BraintrustManager
 */
class Braintrust extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BraintrustManager::class;
    }
}
