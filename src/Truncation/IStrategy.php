<?php namespace Rollbar\Truncation;

interface IStrategy
{
    
    /**
     * @param array $payload
     * @return array
     */
    public function execute(array $payload);
}
