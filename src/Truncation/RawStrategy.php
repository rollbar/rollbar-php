<?php namespace Rollbar\Truncation;

class RawStrategy extends AbstractStrategy
{
    public function execute(array $payload)
    {
        return $payload;
    }
}
