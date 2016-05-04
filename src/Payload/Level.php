<?php namespace Rollbar\Payload;

class Level implements \JsonSerializable
{
    private static $values;

    private static function init()
    {
        if (is_null(self::$values)) {
            self::$values = array(
                "critical" => new Level("critical"),
                "error" => new Level("error"),
                "warning" => new Level("warning"),
                "info" => new Level("info"),
                "debug" => new Level("debug")
            );
        }
    }

    public static function __callStatic($name, $args)
    {
        self::init();
        return self::$values[strtolower($name)];
    }

    private $level;

    private function __construct($level)
    {
        $this->level = $level;
    }

    public function jsonSerialize()
    {
        return $this->level;
    }
}
