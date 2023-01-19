<?php declare(strict_types=1);

namespace Rollbar\Senders;

use Rollbar\Payload\Payload;
use Rollbar\Payload\EncodedPayload;

interface SenderInterface
{
    public function send(EncodedPayload $payload, string $accessToken);
    public function sendBatch(array $batch, string $accessToken): void;
    public function wait(string $accessToken, int $max);

    /**
     * @deprecated 3.2.0 and will be removed in 4.0.0.
     */
    public function toString();
}
