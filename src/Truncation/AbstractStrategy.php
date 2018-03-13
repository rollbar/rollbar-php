<?php namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

class AbstractStrategy implements IStrategy
{
    protected $truncation;
    
    public function __construct($truncation)
    {
        $this->truncation = $truncation;
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
