<?php

namespace EvelynLabs\Braintrust\Exceptions;

use Exception;

class BraintrustApiException extends Exception
{
    /**
     * The HTTP status code from the API response.
     */
    protected int $statusCode;

    /**
     * The response body from the API.
     *
     * @var array<string, mixed>
     */
    protected array $responseBody;

    /**
     * Create a new Braintrust API exception.
     *
     * @param  string  $message  The error message
     * @param  int  $statusCode  The HTTP status code
     * @param  array<string, mixed>  $responseBody  The response body from the API
     */
    public function __construct(string $message, int $statusCode, array $responseBody = [])
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the response body.
     *
     * @return array<string, mixed>
     */
    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
