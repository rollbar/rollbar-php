<?php

namespace Rollbar\TestHelpers;

use Rollbar\RollbarLogger;
use Psr\Log\LoggerTrait;

class StdOutLogger extends RollbarLogger
{
    public function log($level, $toLog, array $context = array())
    {
        echo '[' . get_class($this) . ': '. $level . '] ' . $message;
    }
}
