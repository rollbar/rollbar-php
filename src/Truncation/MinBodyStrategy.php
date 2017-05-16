<?php namespace Rollbar\Truncation;

class MinBodyStrategy extends AbstractStrategy
{
    public function execute(array $payload)
    {
        throw new \Exception('implement MinBodyStrategy::execute');
    }
}