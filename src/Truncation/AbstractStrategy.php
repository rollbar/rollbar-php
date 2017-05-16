<?php namespace Rollbar\Truncation;

class AbstractStrategy implements IStrategy
{
    protected $databuilder;
    
    public function __construct($databuilder)
    {
        $this->databuilder = $databuilder;
    }
    
    public function execute(array $payload)
    {
        return $payload;
    }
}