<?php

namespace Rollbar\TestHelpers;

use Rollbar\RollbarLogger;
use Psr\Log\LoggerTrait;

class StdOutLogger extends RollbarLogger
{
    public function log($level, $message, array $context = array(), $isUncaught = false)
    {
        echo '[' . get_class($this) . ': '. $level . '] ' . $message;
    }
}
