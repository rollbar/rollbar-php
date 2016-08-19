<?php namespace Rollbar;

final class Utilities
{
    // In order to support < 5.6 we had to use __callStatic to define
    // coalesce, because the splat operator was introduced in 5.6
    public static function __callStatic($name, $args)
    {
        if ($name == 'coalesce') {
            return self::coalesceArray($args);
        }
        return null;
    }

    public static function coalesceArray(array $values)
    {
        foreach ($values as $key => $val) {
            if ($val) {
                return $val;
            }
        }
        return null;
    }

    // Modified from: http://stackoverflow.com/a/1176023/456188
    public static function pascalToCamel($input)
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
        if (is_null($input)) {
            if (!$allowNull) {
                throw new \InvalidArgumentException("\$$name must not be null");
            }
            return;
        }

        if (!is_string($input)) {
            throw new \InvalidArgumentException("\$$name must be a string");
        }
        if (!is_null($len) && strlen($input) != $len) {
            throw new \InvalidArgumentException("\$$name must be $len characters long, was '$input'");
        }
    }

    public static function validateBoolean(
        $input,
        $name = "?",
        $allowNull = true
    ) {
        if (is_null($input)) {
            if (!$allowNull) {
                throw new \InvalidArgumentException("\$$name must not be null");
            }
            return;
        }

        if (!is_bool($input)) {
            throw new \InvalidArgumentException("\$$name must be a boolean");
        }
    }

    public static function validateInteger(
        $input,
        $name = "?",
        $minValue = null,
        $maxValue = null,
        $allowNull = true
    ) {
        if (is_null($input)) {
            if (!$allowNull) {
                throw new \InvalidArgumentException("\$$name must not be null");
            }
            return;
        }

        if (!is_integer($input)) {
            throw new \InvalidArgumentException("\$$name must be an integer");
        }
        if (!is_null($minValue) && $input < $minValue) {
            throw new \InvalidArgumentException("\$$name must be >= $minValue");
        }
        if (!is_null($maxValue) && $input > $maxValue) {
            throw new \InvalidArgumentException("\$$name must be <= $maxValue");
        }
    }

    public static function serializeForRollbar(
        $obj,
        array $overrideNames = null,
        array $customKeys = null
    ) {
        $returnVal = array();
        $overrideNames = $overrideNames == null ? array() : $overrideNames;
        $customKeys = $customKeys == null ? array() : $customKeys;

        foreach ($obj as $key => $val) {
            if ($val instanceof \JsonSerializable) {
                $val = $val->jsonSerialize();
            }
            $newKey = array_key_exists($key, $overrideNames)
                ? $overrideNames[$key]
                : Utilities::pascalToCamel($key);
            if (in_array($key, $customKeys)) {
                $returnVal[$key] = $val;
            } elseif (!is_null($val)) {
                $returnVal[$newKey] = $val;
            }
        }

        return $returnVal;
    }
}
