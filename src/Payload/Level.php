<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\LevelFactory;
use Rollbar\SerializerInterface;

class Level implements SerializerInterface
{
    /**
     * Those are PSR-3 compatible loggin levels. They are mapped to Rollbar
     * service supported levels in Level::init()
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    /**
     * @deprecated 1.2.0
     */
    const IGNORED = 'ignored';
    /**
     * @deprecated 1.2.0
     */
    const IGNORE = 'ignore';

    /**
     * @deprecated 1.2.0
     *
     * Usage of Level::error(), Level::warning(), Level::info(), Level::notice(),
     * Level::debug() is no longer supported. It has been replaced with matching
     * class constants, i.e.: Level::ERROR
     */
    public static function __callStatic($name, $args)
    {
        $factory = new LevelFactory();
        $level = $factory->fromName($name);
        
        if (!$level) {
            throw new \Exception("Level '$level' doesn't exist.");
        }
        
        return $level;
    }

    public function __construct(
        private string $level,
        private int $val
    ) {
    }

    public function __toString()
    {
        return $this->level;
    }

    public function toInt(): int
    {
        return $this->val;
    }

    public function serialize()
    {
        return $this->level;
    }
}
