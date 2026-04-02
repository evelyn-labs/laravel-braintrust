<?php

namespace EvelynLabs\Braintrust\Scorers;

interface ScoreContract
{
    /**
     * Get the name of the scorer.
     */
    public function name(): string;

    /**
     * Calculate the score for the given input, output, and expected values.
     *
     * @param  mixed  $input  The input data
     * @param  mixed  $output  The actual output
     * @param  mixed  $expected  The expected output
     * @return float The score between 0.0 and 1.0
     */
    public function score(mixed $input, mixed $output, mixed $expected): float;
}
