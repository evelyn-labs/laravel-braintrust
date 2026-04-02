<?php

namespace EvelynLabs\Braintrust;

use EvelynLabs\Braintrust\Http\BraintrustClient;

class Dataset
{
    /**
     * The Braintrust HTTP client.
     */
    protected BraintrustClient $client;

    /**
     * The dataset ID.
     */
    protected string $id;

    /**
     * Create a new Dataset instance.
     */
    public function __construct(BraintrustClient $client, string $id)
    {
        $this->client = $client;
        $this->id = $id;
    }

    /**
     * Fetch all rows from the dataset.
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function fetch(): iterable
    {
        $response = $this->client->get("/v1/dataset/{$this->id}/fetch");

        return $response['rows'] ?? [];
    }

    /**
     * Insert rows into the dataset.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function insert(array $rows): void
    {
        $this->client->post("/v1/dataset/{$this->id}/insert", [
            'rows' => $rows,
        ]);
    }
}
