<?php namespace Rollbar\Truncation;

interface IStrategy
{
    /**
     * @param array $payload
     * @return array
     */
    public function execute(array &$payload);
    
    /**
     * @param array $payload
     * @return array
     */
    public function applies(array &$payload);
}
