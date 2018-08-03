<?php

namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

class CustomTruncation extends AbstractStrategy
{
    public function execute(EncodedPayload $payload)
    {
        $payload->encode(array('Custom truncation test string'));
        
        var_dump($payload); die();
        
        return $payload;
    }
}