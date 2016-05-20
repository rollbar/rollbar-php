<?php namespace Rollbar;

use Rollbar\Payload\Payload;

interface TransformerInterface
{
    public function transform(Payload $payload, $level, $toLog, $context);
}
