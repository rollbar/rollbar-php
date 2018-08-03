<?php namespace Rollbar\Payload;

use Rollbar\LevelFactory;

class Level implements \Serializable
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

    /**
     * @var string
     */
    private $level;
    private $val;

    public function __construct($level, $val)
    {
        $this->level = $level;
        $this->val = $val;
    }

    public function __toString()
    {
        return $this->level;
    }

    public function toInt()
    {
        return $this->val;
    }

    public function serialize()
    {
        return $this->level;
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
