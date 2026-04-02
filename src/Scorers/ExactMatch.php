<?php

namespace EvelynLabs\Braintrust\Scorers;

class ExactMatch implements ScoreContract
{
    /**
     * Get the name of the scorer.
     */
    public function name(): string
    {
        return 'exact_match';
    }

    /**
     * Calculate the score using strict equality.
     *
     * Returns 1.0 if output exactly matches expected (using ===), 0.0 otherwise.
     *
     * @param  mixed  $input  The input data (unused)
     * @param  mixed  $output  The actual output
     * @param  mixed  $expected  The expected output
     * @return float 1.0 for exact match, 0.0 otherwise
     */
    public function score(mixed $input, mixed $output, mixed $expected): float
    {
        return $output === $expected ? 1.0 : 0.0;
    }
}
