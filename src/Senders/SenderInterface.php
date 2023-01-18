<?php declare(strict_types=1);

namespace Rollbar\Senders;

use Rollbar\Payload\EncodedPayload;
use Rollbar\Payload\Payload;
use Rollbar\Response;

/**
 * The sender interface is used to define how an error report sender should transport payloads to Rollbar Service.
 *
 * An optional constructor can be included in the implementing class. If the constructor is present a single argument
 * with the value of the `senderOptions` config option will be passed to the constructor.
 */
interface SenderInterface
{
    /**
     * Sends the payload to the Rollbar service and returns the response.
     *
     * @param EncodedPayload $payload     The payload to deliver to the Rollbar service.
     * @param string         $accessToken The project access token.
     *
     * @return Response
     */
    public function send(EncodedPayload $payload, string $accessToken): Response;

    /**
     * Sends an array of payloads to the Rollbar service.
     *
     * @param Payload[] $batch       The array of {@see Payload} instances.
     * @param string    $accessToken The project access token.
     *
     * @return void
     */
    public function sendBatch(array $batch, string $accessToken): void;

    /**
     * Method used to keep the batch send process alive until all or $max number of Payloads are sent, which ever comes
     * first.
     *
     * @param string $accessToken The project access token.
     * @param int    $max         The maximum payloads to send before stopping the batch send process.
     *
     * @return void
     */
    public function wait(string $accessToken, int $max): void;

    /**
     * Returns true if the access token is required by the sender to send the payload. In cases where an intermediary
     * sender is being used like Fluentd.
     *
     * @return bool
     * @since 4.0.0
     */
    public function requireAccessToken(): bool;
}
