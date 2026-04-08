<?php

// =============================================================================
// Debugging Functions
// =============================================================================

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'debug_backtrace'])
    ->each->not->toBeUsed();

// =============================================================================
// Namespace Organization
// =============================================================================

arch('interfaces are properly defined')
    ->expect('EvelynLabs\Braintrust\Scorers\ScoreContract')
    ->toBeInterface();

// =============================================================================
// HTTP Layer Architecture
// =============================================================================

arch('http client has proper dependencies')
    ->expect('EvelynLabs\Braintrust\Http')
    ->toOnlyUse([
        'Illuminate\Support\Facades\Http',
        'Illuminate\Http\Client',
        'Illuminate\Contracts',
        'EvelynLabs\Braintrust\Exceptions',
        'EvelynLabs\Braintrust\Http',
    ]);

arch('http classes have correct suffix')
    ->expect('EvelynLabs\Braintrust\Http')
    ->classes()
    ->toHaveSuffix('Client');

// =============================================================================
// Console/Command Architecture
// =============================================================================

arch('commands extend base command')
    ->expect('EvelynLabs\Braintrust\Console')
    ->classes()
    ->toExtend('Illuminate\Console\Command');

arch('commands have command suffix')
    ->expect('EvelynLabs\Braintrust\Console')
    ->classes()
    ->toHaveSuffix('Command');

// =============================================================================
// Facade Architecture
// =============================================================================

arch('facades extend base facade')
    ->expect('EvelynLabs\Braintrust\Facades')
    ->classes()
    ->toExtend('Illuminate\Support\Facades\Facade');

// =============================================================================
// Exception Architecture
// =============================================================================

arch('exceptions extend base exception')
    ->expect('EvelynLabs\Braintrust\Exceptions')
    ->classes()
    ->toExtend('Exception');

arch('exceptions have exception suffix')
    ->expect('EvelynLabs\Braintrust\Exceptions')
    ->classes()
    ->toHaveSuffix('Exception');

// =============================================================================
// Scorer Architecture
// =============================================================================

arch('scorers implement contract')
    ->expect('EvelynLabs\Braintrust\Scorers')
    ->classes()
    ->toImplement('EvelynLabs\Braintrust\Scorers\ScoreContract');

arch('scorer contract is interface')
    ->expect('EvelynLabs\Braintrust\Scorers\ScoreContract')
    ->toBeInterface();

// =============================================================================
// Dependency Rules
// =============================================================================

arch('domain layer does not depend on infrastructure')
    ->expect('EvelynLabs\Braintrust\Scorers')
    ->not->toUse('Illuminate\Http');

arch('service provider only binds services')
    ->expect('EvelynLabs\Braintrust\BraintrustServiceProvider')
    ->toOnlyUse([
        'Illuminate',
        'Spatie\LaravelPackageTools',
        'EvelynLabs\Braintrust',
    ]);

// =============================================================================
// Naming Conventions
// =============================================================================

arch('managers have manager suffix')
    ->expect('EvelynLabs\Braintrust\BraintrustManager')
    ->toHaveSuffix('Manager');

arch('experiments have experiment suffix')
    ->expect('EvelynLabs\Braintrust\Experiment')
    ->toHaveSuffix('Experiment');

arch('datasets have dataset suffix')
    ->expect('EvelynLabs\Braintrust\Dataset')
    ->toHaveSuffix('Dataset');

arch('spans have span suffix')
    ->expect('EvelynLabs\Braintrust\Span')
    ->toHaveSuffix('Span');
