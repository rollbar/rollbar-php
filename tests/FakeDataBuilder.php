<?php namespace Rollbar;

use Rollbar\DataBuilderInterface;

class FakeDataBuilder implements DataBuilderInterface
{
    public static $args = array();
    public static $logged = array();

    public function __construct($arr)
    {
        self::$args[] = $arr;
    }

    public function makeData($level, $toLog, $context)
    {
        self::$logged[] = array($level, $toLog, $context);
    }
    
    public function setCustom()
    {
    }
}
