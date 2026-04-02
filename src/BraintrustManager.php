<?php

namespace EvelynLabs\Braintrust;

use EvelynLabs\Braintrust\Http\BraintrustClient;

class BraintrustManager
{
    /**
     * The Braintrust HTTP client.
     */
    protected BraintrustClient $client;

    /**
     * The configuration array.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new Braintrust manager instance.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(BraintrustClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Create an experiment instance.
     */
    public function experiment(string $name): Experiment
    {
        return new Experiment(
            $this->client,
            $name,
            $this->config['project'] ?? null
        );
    }

    /**
     * Create a dataset instance.
     */
    public function dataset(string $id): Dataset
    {
        return new Dataset($this->client, $id);
    }
}
