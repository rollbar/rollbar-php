<?php namespace Rollbar\Senders;

use Rollbar\Payload\Payload;
use Rollbar\ResponseHandlerInterface;

interface SenderInterface
{
    public function send(Payload $payload, $accessToken);
}
