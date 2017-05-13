<?php namespace Rollbar\Truncation;

class RawStrategy implements IStrategy
{
    public function execute(array $payload)
    {
        return $payload;
    }
}