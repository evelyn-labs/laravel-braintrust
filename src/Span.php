<?php

namespace EvelynLabs\Braintrust;

use Illuminate\Support\Str;

class Span
{
    /**
     * The unique identifier for the span.
     */
    public string $id;

    /**
     * The input data.
     */
    public mixed $input;

    /**
     * The output data.
     */
    public mixed $output;

    /**
     * The expected output.
     */
    public mixed $expected;

    /**
     * The scores for this span.
     *
     * @var array<string, float>
     */
    public array $scores = [];

    /**
     * Metadata associated with the span.
     *
     * @var array<string, mixed>
     */
    public array $metadata = [];

    /**
     * Metrics associated with the span.
     *
     * @var array<string, mixed>
     */
    public array $metrics = [];

    /**
     * Create a new Span instance.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->id = $attributes['id'] ?? (string) Str::uuid();
        $this->input = $attributes['input'] ?? null;
        $this->output = $attributes['output'] ?? null;
        $this->expected = $attributes['expected'] ?? null;
        $this->scores = $attributes['scores'] ?? [];
        $this->metadata = $attributes['metadata'] ?? [];
        $this->metrics = $attributes['metrics'] ?? [];
    }

    /**
     * Convert the span to an array for API payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {

        $data = [
        'id' => $this->id,
        'input' => $this->input,
        'output' => $this->output,
        'expected' => $this->expected,
    ];
    if (! empty($this->scores)) {
        $data['scores'] = $this->scores;
    }
    if (! empty($this->metadata)) {
        $data['metadata'] = $this->metadata;
    }
    if (! empty($this->metrics)) {
        $data['metrics'] = $this->metrics;
    }
        return $data;
    }
}
