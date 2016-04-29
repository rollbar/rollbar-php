<?php namespace Rollbar;

final class Utilities
{
    public static function pascaleToCamel($input)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $input)), '_');
    }

    public static function isString($input, $name, $len)
    {
        if (!is_string($input)) {
            throw new \InvalidArgumentException("\$$name must ba a string");
        }
        if (!is_null($len) && strlen($input) != $len) {
            throw new \InvalidArgumentException("\$$name must be $len characters long, was '$input'");
        }
    }
}
