<?php namespace Rollbar\Payload;

class Level implements \JsonSerializable
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
    
    private static $values;

    private static function init()
    {
        if (is_null(self::$values)) {
            self::$values = array(
                self::EMERGENCY => new Level("critical", 100000),
                self::ALERT => new Level("critical", 100000),
                self::CRITICAL => new Level("critical", 100000),
                self::ERROR => new Level("error", 10000),
                self::WARNING => new Level("warning", 1000),
                self::NOTICE => new Level("info", 100),
                self::INFO => new Level("info", 100),
                self::DEBUG => new Level("debug", 10),
                self::IGNORED => new Level("ignore", 0),
                self::IGNORE => new Level("ignore", 0)

            );
        }
    }

    public static function __callStatic($name, $args)
    {
        return self::fromName($name);
    }

    /**
     * @param string $name level name
     * @return Level
     */
    public static function fromName($name)
    {
        self::init();
        $name = strtolower($name);
        return array_key_exists($name, self::$values) ? self::$values[$name] : null;
    }

    /**
     * @var string
     */
    private $level;
    private $val;

    private function __construct($level, $val)
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

    public function jsonSerialize()
    {
        return $this->level;
    }
}
