<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Level;

class LevelFactory
{
    /**
     * Holds the list of log levels as [string => Level].
     *
     * @var array|null
     */
    private static ?array $levels = null;

    /**
     * Returns the array of levels as [string => Level].
     *
     * @return array
     */
    private static function getLevels(): array
    {
        if (null === self::$levels) {
            self::$levels = array(
                Level::EMERGENCY => new Level("critical", 100000),
                Level::ALERT     => new Level("critical", 100000),
                Level::CRITICAL  => new Level("critical", 100000),
                Level::ERROR     => new Level("error", 10000),
                Level::WARNING   => new Level("warning", 1000),
                Level::NOTICE    => new Level("info", 100),
                Level::INFO      => new Level("info", 100),
                Level::DEBUG     => new Level("debug", 10),
            );
        }

        return self::$levels;
    }

    /**
     * Returns the {@see Level} instance for a given log level. If the log level
     * is invalid null will be returned.
     *
     * @param string $name level name
     *
     * @return Level|null
     */
    public static function fromName(string $name): ?Level
    {
        $name = strtolower($name);
        return self::getLevels()[$name] ?? null;
    }

    /**
     * Check if the provided level is a valid level.
     *
     * @param string $level
     *
     * @return bool
     */
    public static function isValidLevel(string $level): bool
    {
        return self::fromName($level) !== null;
    }
}
