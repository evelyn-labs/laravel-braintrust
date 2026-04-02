<?php

namespace EvelynLabs\Braintrust\Commands;

use Illuminate\Console\Command;

class BraintrustCommand extends Command
{
    public $signature = 'laravel-braintrust';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
