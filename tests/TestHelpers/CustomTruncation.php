<?php

namespace Rollbar\TestHelpers;

use \Rollbar\Truncation\AbstractStrategy;
use \Rollbar\Payload\EncodedPayload;

class CustomTruncation extends AbstractStrategy
{
    public function execute(EncodedPayload $payload)
    {
        $payload->encode(array(
            "data" => array(
                "body" => array(
                    "message" => array(
                        "body" => array(
                            "value" => 'Custom truncation test string'
                        )
                    )
                )
            )
        ));
        
        return $payload;
    }
}
