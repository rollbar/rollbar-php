<?php namespace Rollbar\Senders;

use Rollbar\Payload\Payload;

interface SenderInterface
{
    public function send($scrubbedPayload, $accessToken);
}
