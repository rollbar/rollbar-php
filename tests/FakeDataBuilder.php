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
    
    public function setCustom(array $config): void
    {
    }

    public function addCustom(string $key, mixed $data): void
    {
    }

    public function removeCustom(string $key): void
    {
    }

    public function getCustom(): ?array
    {
        return null;
    }

    public function generateErrorWrapper(int $errno, string $errstr, ?string $errfile, ?int $errline): ErrorWrapper
    {
        return new ErrorWrapper($errno, $errstr, $errfile, $errline, [], new Utilities());
    }
}
