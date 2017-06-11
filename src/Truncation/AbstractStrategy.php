<?php namespace Rollbar\Truncation;

class AbstractStrategy implements IStrategy
{
    protected $dataBuilder;
    
    public function __construct($dataBuilder)
    {
        $this->dataBuilder = $dataBuilder;
    }
    
    public function execute(array $payload)
    {
        return $payload;
    }
}
