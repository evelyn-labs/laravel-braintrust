<?php

namespace EvelynLabs\Braintrust\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \EvelynLabs\Braintrust\Braintrust
 */
class Braintrust extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \EvelynLabs\Braintrust\Braintrust::class;
    }
}
