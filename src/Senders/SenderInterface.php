<?php namespace Rollbar\Senders;

use Rollbar\Payload\Payload;
use Rollbar\Payload\EncodedPayload;

interface SenderInterface
{
    public function send(EncodedPayload $payload, $accessToken);
    public function sendBatch($batch, $accessToken);
    public function wait($accessToken, $max);
    public function toString();
}
