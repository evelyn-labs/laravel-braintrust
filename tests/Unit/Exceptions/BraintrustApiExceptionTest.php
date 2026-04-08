<?php

use EvelynLabs\Braintrust\Exceptions\BraintrustApiException;

it('stores status code in exception', function () {
    $exception = new BraintrustApiException('API Error', 404);

    expect($exception->getStatusCode())->toBe(404);
});

it('stores response body in exception', function () {
    $responseBody = ['error' => 'Not found', 'details' => 'Resource does not exist'];
    $exception = new BraintrustApiException('API Error', 404, $responseBody);

    expect($exception->getResponseBody())->toBe($responseBody);
});

it('stores status code as exception code', function () {
    $exception = new BraintrustApiException('API Error', 422);

    expect($exception->getCode())->toBe(422);
});
