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
            $newKey = array_key_exists($key, $overrideNames)
                ? $overrideNames[$key]
                : Utilities::pascaleToCamel($key);
            if (!is_null($val) || array_key_exists($key, $customKeys)) {
                $returnVal[$newKey] = $val;
            }
        }

        return $returnVal;
    }
}
