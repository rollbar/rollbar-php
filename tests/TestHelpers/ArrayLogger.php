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
    public function log($level, $message, array $context = []): void
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

    /**
     * Returns the index of the first instance of a matching log in the logs. Returns -1 if no matching log is found.
     *
     * @param string            $level   The log level of the message to search for.
     * @param Stringable|string $message The message body to search for.
     *
     * @return int
     */
    public function indexOf(string $level, Stringable|string $message): int
    {
        $str = self::stringify($level, $message);
        foreach ($this->logs as $index => $log) {
            if ($str === $log) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Returns the index of the first instance of a matching level and message pattern in the logs. Returns -1 if no
     * matching log is found.
     *
     * @param string $level   The log level of the message to search for. You can pass '.+' to match any level.
     * @param string $pattern The regex pattern to search for.
     *
     * @return int
     */
    public function indexOfRegex(string $level, string $pattern): int
    {
        $pattern = '/\[' . $level . '\].*' . $pattern . '/';
        foreach ($this->logs as $index => $log) {
            if (preg_match($pattern, $log)) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Checks the log at the given index to see if it matches the given level and message pattern. Returns true if it
     * matches, false otherwise.
     *
     * @param int    $index   The index of the log to check.
     * @param string $level   The log level of the message to search for. You can pass '.+' to match any level.
     * @param string $pattern The regex pattern to search for.
     *
     * @return bool
     */
    public function indexMatchesRegex(int $index, string $level, string $pattern): bool
    {
        if ($index < 0 || $index >= count($this->logs)) {
            return false;
        }
        $log     = $this->logs[$index];
        $pattern = '/\[' . $level . '\].*' . $pattern . '/';
        if (preg_match($pattern, $log)) {
            return true;
        }
        return false;
    }
}
