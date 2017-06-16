<?php namespace Rollbar;

use Rollbar\Payload\Level;

class LevelFactory
{
    
    private static $values;

    private static function init()
    {
        if (is_null(self::$values)) {
            self::$values = array(
                Level::EMERGENCY => new Level("critical", 100000),
                Level::ALERT => new Level("critical", 100000),
                Level::CRITICAL => new Level("critical", 100000),
                Level::ERROR => new Level("error", 10000),
                Level::WARNING => new Level("warning", 1000),
                Level::NOTICE => new Level("info", 100),
                Level::INFO => new Level("info", 100),
                Level::DEBUG => new Level("debug", 10),
                Level::IGNORED => new Level("ignore", 0),
                Level::IGNORE => new Level("ignore", 0)

            );
        }
    }

    /**
     * @param string $name level name
     *
     * @return Level
     */
    public function fromName($name)
    {
        self::init();
        $name = strtolower($name);
        return array_key_exists($name, self::$values) ? self::$values[$name] : null;
    }
    
    /**
     * Check if the provided level is a valid level
     *
     * @param string $level
     *
     * @return string
     */
    public function isValidLevel($level)
    {
        return $this->fromName($level) ? true : false;
    }
}
