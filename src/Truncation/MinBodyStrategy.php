<?php namespace Rollbar\Truncation;

class MinBodyStrategy implements IStrategy
{
    public function execute(array $payload)
    {
        throw new \Exception('implement MinBodyStrategy::execute');
    }
}