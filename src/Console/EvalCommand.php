<?php

namespace EvelynLabs\Braintrust\Console;

use EvelynLabs\Braintrust\Facades\Braintrust;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class EvalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'braintrust:eval {path : Path to the evaluation config file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a Braintrust evaluation from a config file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = require $path;

        // Validate required keys
        $required = ['experiment', 'dataset', 'task', 'scorers'];
        foreach ($required as $key) {
            if (! isset($data[$key])) {
                $this->error("Missing required key: {$key}");

                return self::FAILURE;
            }
        }

        $this->info("Running experiment: {$data['experiment']}");

        // Run the experiment
        $results = Braintrust::experiment($data['experiment'])->run(
            $data['dataset'],
            $data['task'],
            $data['scorers']
        );

        // Display per-row scores
        $this->newLine();
        $this->info('Per-row scores:');

        $rows = [];
        foreach ($results['spans'] as $index => $span) {
            $row = [
                'Row' => $index + 1,
                'Input' => $this->truncate(json_encode($span->input), 50),
            ];
            foreach ($span->scores as $name => $score) {
                $row[$name] = number_format($score, 2);
            }
            $rows[] = $row;
        }

        if (! empty($rows)) {
            $this->table(array_keys($rows[0]), $rows);
        }

        // Display aggregate scores
        $this->newLine();
        $this->info('Aggregate scores:');

        $aggregateRows = [];
        foreach ($results['aggregates'] as $name => $aggregate) {
            $aggregateRows[] = [
                'Scorer' => $name,
                'Average' => number_format($aggregate['avg'], 4),
                'Count' => $aggregate['count'],
            ];
        }

        $this->table(['Scorer', 'Average', 'Count'], $aggregateRows);

        // Check thresholds
        $thresholds = Config::get('braintrust.thresholds', []);
        $failed = [];

        foreach ($results['aggregates'] as $name => $aggregate) {
            if (isset($thresholds[$name])) {
                $threshold = $thresholds[$name];
                if ($aggregate['avg'] < $threshold) {
                    $failed[] = [
                        'scorer' => $name,
                        'avg' => $aggregate['avg'],
                        'threshold' => $threshold,
                    ];
                }
            }
        }

        // Report results
        $this->newLine();
        if (empty($failed)) {
            $this->info('All scorers passed their thresholds!');

            return self::SUCCESS;
        } else {
            $this->error('Some scorers failed to meet their thresholds:');
            foreach ($failed as $fail) {
                $this->error(sprintf(
                    '  %s: %.4f (threshold: %.4f)',
                    $fail['scorer'],
                    $fail['avg'],
                    $fail['threshold']
                ));
            }

            return self::FAILURE;
        }
    }

    /**
     * Truncate a string to a maximum length.
     */
    protected function truncate(string $string, int $maxLength): string
    {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        return substr($string, 0, $maxLength - 3).'...';
    }
}
