<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Payload;

/**
 * The FilterInterface allows a custom payload filter to be used. It should be passed to the configs with the 'filter'
 * key. A custom filter should implement this interface.
 */
interface FilterInterface
{
    /**
     * Method called to determine if a payload should be sent to the Rollbar service. If true is returned the payload
     * will not be sent.
     *
     * @param Payload $payload    The payload instance that may be sent.
     * @param bool    $isUncaught True if the payload represents an error that was caught by one of the Rollbar
     *                            exception or error handlers.
     *
     * @return bool
     */
    public function shouldSend(Payload $payload, bool $isUncaught): bool;
}
