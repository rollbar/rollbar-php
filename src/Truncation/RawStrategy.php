<?php namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

class RawStrategy extends AbstractStrategy
{
    public function execute(EncodedPayload $payload)
    {
        return $payload;
    }
}
