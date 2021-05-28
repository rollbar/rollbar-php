<?php declare(strict_types=1);

namespace Rollbar\Senders;

use Rollbar\Payload\Payload;
use Rollbar\Payload\EncodedPayload;

interface SenderInterface
{
    public function send(EncodedPayload $payload, string $accessToken);
    public function sendBatch(array $batch, string $accessToken): void;
    public function wait(string $accessToken, int $max);
    public function toString();
}
