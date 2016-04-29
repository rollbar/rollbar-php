<?php namespace Rollbar;

final class Utilities
{
    // Modified from: http://stackoverflow.com/a/1176023/456188
    public static function pascaleToCamel($input)
    {
        $s1 = preg_replace('/([^_])([A-Z][a-z]+)/', '$1_$2', $input);
        return strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $s1));
    }

    public static function validateString(
        $input,
        $name = "?",
        $len = null,
        $allowNull = true
    ) {
    
        if (!$allowNull && is_null($input)) {
            throw new \InvalidArgumentException("\$$name must not be null");
        }
        if (!is_null($input) && !is_string($input)) {
            throw new \InvalidArgumentException("\$$name must be a string");
        }
        if (!is_null($input) && !is_null($len) && strlen($input) != $len) {
            throw new \InvalidArgumentException("\$$name must be $len characters long, was '$input'");
        }
    }
}
