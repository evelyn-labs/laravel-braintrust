<?php

namespace EvelynLabs\Braintrust\Scorers;

class Contains implements ScoreContract
{
    /**
     * Get the name of the scorer.
     */
    public function name(): string
    {
        return 'contains';
    }

    /**
     * Calculate the score by checking if output contains the expected substring.
     *
     * Returns 1.0 if output (string) contains expected (string), 0.0 otherwise.
     *
     * @param mixed $input The input data (unused)
     * @param mixed $output The actual output (should be string)
     * @param mixed $expected The expected substring (should be string)
     * @return float 1.0 if output contains expected, 0.0 otherwise
     */
    public function score(mixed $input, mixed $output, mixed $expected): float
    {
        if (! is_string($output) || ! is_string($expected)) {
            return 0.0;
        }

        return str_contains($output, $expected) ? 1.0 : 0.0;
    }
}
