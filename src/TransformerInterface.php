<?php namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;

interface TransformerInterface
{
    /**
     * @param Payload $payload
     * @param Level $level
     * @param \Exception | \Throwable $toLog
     * @param $context
     * @return Payload
     */
    public function transform(Payload $payload, $level, $toLog, $context);
}
