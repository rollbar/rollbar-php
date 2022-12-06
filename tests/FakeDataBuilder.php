<?php namespace Rollbar;

use Rollbar\Payload\Body;
use Rollbar\Payload\Data;
use Rollbar\Payload\Message;
use Stringable;
use Throwable;

class FakeDataBuilder implements DataBuilderInterface
{
    public static array $args = array();
    public static array $logged = array();

    public function __construct($arr)
    {
        self::$args[] = $arr;
    }

    public function makeData(string $level, Throwable|string|Stringable $toLog, array $context): Data
    {
        self::$logged[] = array($level, $toLog, $context);

        return new Data('test', new Body(new Message('test')));
    }
    
    public function setCustom(): void
    {
    }
}
