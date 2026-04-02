<?php

namespace EvelynLabs\Braintrust;

use EvelynLabs\Braintrust\Http\BraintrustClient;
use EvelynLabs\Braintrust\Scorers\ScoreContract;

class Experiment
{
    /**
     * The Braintrust HTTP client.
     */
    protected BraintrustClient $client;

    /**
     * The experiment name.
     */
    protected string $name;

    /**
     * The project name.
     */
    protected ?string $project;

    /**
     * The experiment ID.
     */
    protected ?string $experimentId = null;

    /**
     * Create a new Experiment instance.
     */
    public function __construct(BraintrustClient $client, string $name, ?string $project = null)
    {
        $this->client = $client;
        $this->name = $name;
        $this->project = $project;
    }

    /**
     * Create or resume the experiment.
     */
    public function createOrResume(): string
    {
        $body = [
            'name' => $this->name,
            'ensure_new' => true,
        ];

        if ($this->project !== null && $this->project !== '') {
            $body['project_id'] = $this->project;
        }

        $response = $this->client->post('/v1/experiment', $body);

        $this->experimentId = $response['id'] ?? null;

        if ($this->experimentId === null) {
            throw new \RuntimeException('Failed to create or resume experiment: no ID returned');
        }

        return $this->experimentId;
    }

    /**
     * Run the experiment on a dataset.
     *
     * @param  iterable<int, array<string, mixed>>  $dataset  The dataset rows with 'input' and 'expected' keys
     * @param  callable  $task  The task callable that takes input and returns output
     * @param  array<int, ScoreContract>  $scorers  Array of scorer instances
     * @return array<string, mixed> Results with 'spans' and 'aggregates'
     */
    public function run(iterable $dataset, callable $task, array $scorers): array
    {
        // Ensure experiment is created
        if ($this->experimentId === null) {
            $this->createOrResume();
        }

        $spans = [];
        $scoreSums = [];
        $scoreCounts = [];

        foreach ($dataset as $row) {
            $startTime = microtime(true);

            // Execute the task
            $output = $task($row['input'] ?? null);

            $endTime = microtime(true);

            // Score the output
            $rowScores = [];
            foreach ($scorers as $scorer) {
                $score = $scorer->score(
                    $row['input'] ?? null,
                    $output,
                    $row['expected'] ?? null
                );
                $rowScores[$scorer->name()] = $score;

                // Track aggregates
                $scoreName = $scorer->name();
                if (! isset($scoreSums[$scoreName])) {
                    $scoreSums[$scoreName] = 0.0;
                    $scoreCounts[$scoreName] = 0;
                }
                $scoreSums[$scoreName] += $score;
                $scoreCounts[$scoreName]++;
            }

            // Create span
            $span = new Span([
                'input' => $row['input'] ?? null,
                'output' => $output,
                'expected' => $row['expected'] ?? null,
                'scores' => $rowScores,
                'metrics' => [
                    'start' => $startTime,
                    'end' => $endTime,
                    'duration' => $endTime - $startTime,
                ],
            ]);

            $spans[] = $span;

            // Insert span to experiment
            $this->client->post(
                "/v1/experiment/{$this->experimentId}/insert",
                [
                    'events' => [$span->toArray()],
                ]
            );
        }

        // Calculate aggregates
        $aggregates = [];
        foreach ($scoreSums as $scoreName => $sum) {
            $aggregates[$scoreName] = [
                'avg' => $sum / $scoreCounts[$scoreName],
                'count' => $scoreCounts[$scoreName],
            ];
        }

        return [
            'spans' => $spans,
            'aggregates' => $aggregates,
        ];
    }
}
