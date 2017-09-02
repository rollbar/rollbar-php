<?php namespace Rollbar;

final class Utilities
{
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
        array $customKeys = null
    ) {
        $returnVal = array();

        foreach ($obj as $key => $val) {
            if ($val instanceof \JsonSerializable) {
                $val = $val->jsonSerialize();
            }
            if ($customKeys !== null && in_array($key, $customKeys)) {
                $returnVal[$key] = $val;
            } elseif (!is_null($val)) {
                $returnVal[$key] = $val;
            }
        }

        return $returnVal;
    }

    // from http://www.php.net/manual/en/function.uniqid.php#94959
    public static function uuid4()
    {
        mt_srand();
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
