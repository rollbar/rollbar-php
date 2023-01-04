<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Payload;

/**
 * The response handler interface allows the processing of responses from the Rollbar service after a log message or
 * error report has been sent to Rollbar. A custom response handler FQCN may be passed in the config array with the key
 * "responseHandler". The custom handler class constructor may be a single argument by including it in the config array
 * with the "responseHandlerOptions" key. If the "responseHandlerOptions" key does not exist an empty array will be
 * passed to the constructor.
 */
interface ResponseHandlerInterface
{
    /**
     * The handleResponse method is called with the response from the Rollbar service after an error or log message has
     * been sent.
     *
     * @param Payload  $payload  The payload object that was sent to Rollbar.
     * @param Response $response The response that was returned. If the response status code is 0, it likely represents
     *                           an ignored error that was never sent.
     */
    public function handleResponse(Payload $payload, Response $response): void;
}
