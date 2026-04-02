<?php

namespace EvelynLabs\Braintrust\Scorers;

class LLMScorer implements ScoreContract
{
    /**
     * The callable to invoke the LLM.
     *
     * @var callable
     */
    protected $llmCallable;

    /**
     * The rubric prompt with placeholders.
     */
    protected string $rubricPrompt;

    /**
     * The name of the scorer.
     */
    protected string $name;

    /**
     * Create a new LLM scorer instance.
     *
     * @param  callable  $llmCallable  The callable to invoke the LLM (string prompt -> string response)
     * @param  string  $rubricPrompt  The rubric prompt with {input}, {output}, {expected} placeholders
     * @param  string|null  $name  Optional custom name for the scorer
     */
    public function __construct(callable $llmCallable, string $rubricPrompt, ?string $name = null)
    {
        $this->llmCallable = $llmCallable;
        $this->rubricPrompt = $rubricPrompt;
        $this->name = $name ?? 'llm_scorer';
    }

    /**
     * Get the name of the scorer.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Calculate the score using an LLM to evaluate the output.
     *
     * @param  mixed  $input  The input data
     * @param  mixed  $output  The actual output
     * @param  mixed  $expected  The expected output
     * @return float The normalized score between 0.0 and 1.0
     */
    public function score(mixed $input, mixed $output, mixed $expected): float
    {
        $formattedPrompt = $this->formatPrompt($input, $output, $expected);

        $response = ($this->llmCallable)($formattedPrompt);

        return $this->parseScore($response);
    }

    /**
     * Format the prompt by replacing placeholders.
     */
    protected function formatPrompt(mixed $input, mixed $output, mixed $expected): string
    {
        $replacements = [
            '{input}' => $this->stringify($input),
            '{output}' => $this->stringify($output),
            '{expected}' => $this->stringify($expected),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->rubricPrompt
        );
    }

    /**
     * Convert a value to a string representation.
     */
    protected function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    /**
     * Parse the score from the LLM response.
     *
     * Handles responses like "0.85", "Score: 0.85", "The score is 0.85", etc.
     */
    protected function parseScore(string $response): float
    {
        // Try to find a float number in the response
        if (preg_match('/(\d+\.?\d*)/', $response, $matches)) {
            $score = (float) $matches[1];

            // Normalize to 0-1 range if needed
            if ($score > 1.0 && $score <= 100.0) {
                $score = $score / 100.0;
            }

            // Clamp to valid range
            return max(0.0, min(1.0, $score));
        }

        // If no number found, return 0.0
        return 0.0;
    }
}
