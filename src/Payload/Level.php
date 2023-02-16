<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;

/**
 * Each constant is a PSR-3 compatible logging level. They are mapped to Rollbar
 * service supported levels in {@see LevelFactory::getLevels()}.
 */
class Level implements SerializerInterface
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * Instantiates the level object with the Rollbar service compatible error
     * levels.
     *
     * @param string $level The Rollbar service error level.
     * @param int    $val   The Rollbar service numeric error level.
     */
    public function __construct(
        private string $level,
        private int $val
    ) {
    }

    /**
     * Returns the Rollbar service error level.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->level;
    }

    /**
     * Returns the Rollbar service numeric error level.
     *
     * @return int
     */
    public function toInt(): int
    {
        return $this->val;
    }

    /**
     * Returns the serialized Rollbar service level.
     *
     * @return string
     */
    public function serialize()
    {
        return $this->level;
    }
}
