<?php namespace Rollbar\Truncation;

class AbstractStrategy implements IStrategy
{
    protected $truncation;
    
    public function __construct($truncation)
    {
        $this->truncation = $truncation;
    }
    
    public function execute(array $payload)
    {
        return $payload;
    }
}
