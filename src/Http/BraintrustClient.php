<?php

namespace EvelynLabs\Braintrust\Http;

use EvelynLabs\Braintrust\Exceptions\BraintrustApiException;
use Illuminate\Support\Facades\Http;

class BraintrustClient
{
    /**
     * The configuration array.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new Braintrust client instance.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Make a GET request to the Braintrust API.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $query Query parameters
     * @return array<string, mixed> The response data
     * @throws BraintrustApiException
     */
    public function get(string $path, array $query = []): array
    {
        $response = Http::withOptions([
            'timeout' => $this->config['timeout'] ?? 30,
        ])
            ->withToken($this->config['api_key'])
            ->baseUrl($this->config['base_url'])
            ->get($path, $query);

        if (! $response->successful()) {
            throw new BraintrustApiException(
                "Braintrust API GET request failed: {$response->body()}",
                $response->status(),
                $response->json() ?? []
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Make a POST request to the Braintrust API.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $data The request body data
     * @return array<string, mixed> The response data
     * @throws BraintrustApiException
     */
    public function post(string $path, array $data = []): array
    {

        $response = Http::withOptions([
            'timeout' => $this->config['timeout'] ?? 30,
        ])
            ->withToken($this->config['api_key'])
            ->baseUrl($this->config['base_url'])
            ->asJson()
            ->post($path, $data);

        if (! $response->successful()) {
            throw new BraintrustApiException(
                "Braintrust API POST request failed: {$response->body()}",
                $response->status(),
                $response->json() ?? []
            );
        }

        return $response->json() ?? [];
    }
}
