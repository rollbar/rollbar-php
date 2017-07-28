<?php namespace Rollbar\Senders;

use Rollbar\Payload\Payload;

interface SenderInterface
{
    public function send($scrubbedPayload, $accessToken);
    public function sendBatch($batch, $accessToken);
    public function wait($accessToken, $max);
}
