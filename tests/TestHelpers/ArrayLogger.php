<?php

namespace Rollbar\TestHelpers;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Simple logger that saves logs to an array.
 *
 * @since 4.0.0
 */
class ArrayLogger extends AbstractLogger
{
    public array $logs = [];

    /**
     * Serialize a level and message into a single string.
     *
     * @param                   $level
     * @param Stringable|string $message
     *
     * @return string
     */
    private static function stringify($level, Stringable|string $message): string
    {
        return '[' . $level . ']' . $message;
    }

    /**
     * @inheritDoc
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logs[] = self::stringify($level, $message);
    }

    /**
     * Returns the number of instances of a log in the logs.
     *
     * @param                   $level
     * @param Stringable|string $message
     *
     * @return int
     */
    public function count($level, Stringable|string $message): int
    {
        $count = 0;
        $str   = self::stringify($level, $message);
        foreach ($this->logs as $log) {
            if ($str === $log) {
                $count++;
            }
        }
        return $count;
    }
}
