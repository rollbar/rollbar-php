<?php namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

class AbstractStrategy implements IStrategy
{
    public function __construct(protected $truncation)
    {
    }
    
    public function execute(EncodedPayload $payload)
    {
        return $payload;
    }
    
    public function applies(EncodedPayload $payload)
    {
        return true;
    }
}
