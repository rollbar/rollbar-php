<?php declare(strict_types=1);

namespace Rollbar;

use Serializable;

final class Utilities
{
    private static $ObjectHashes;
    
    public static function isWindows()
    {
        return php_uname('s') == 'Windows NT';
    }

    /**
     * Validate that the given $input is a string or null if $allowNull is true and that it has as many characters as
     * one of the given $len.
     *
     * @param mixed $input The value to validate.
     * @param string $name The name of the variable being validated.
     * @param int|int[]|null $len The length(s) to validate against. Can be a single integer or an array of integers.
     * @param bool $allowNull Whether to allow null values.
     * @return void
     *
     * @since 1.0.0
     * @since 4.1.3 Added support for array of lengths.
     */
    public static function validateString(
        mixed $input,
        string $name = "?",
        int|array|null $len = null,
        bool $allowNull = true
    ): void {
        if (is_null($input)) {
            if (!$allowNull) {
                throw new \InvalidArgumentException("\$$name must not be null");
            }
            return;
        }

        if (!is_string($input)) {
            throw new \InvalidArgumentException("\$$name must be a string");
        }
        if (null === $len) {
            return;
        }
        if (!is_array($len)) {
            $len = [$len];
        }
        foreach ($len as $l) {
            if (strlen($input) == $l) {
                return;
            }
        }
        $lens = implode(", ", $len);
        throw new \InvalidArgumentException("\$$name must be $lens characters long, was '$input'");
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

        if (!is_int($input)) {
            throw new \InvalidArgumentException("\$$name must be an integer");
        }
        if (!is_null($minValue) && $input < $minValue) {
            throw new \InvalidArgumentException("\$$name must be >= $minValue");
        }
        if (!is_null($maxValue) && $input > $maxValue) {
            throw new \InvalidArgumentException("\$$name must be <= $maxValue");
        }
    }

    /**
     * Serialize all, or the given keys, from the given object and store it
     * in this class's internal store (see self::$ObjectHashes).
     */
    public static function serializeForRollbarInternal($obj, ?array $customKeys = null)
    {
        return self::serializeForRollbar($obj, $customKeys, self::$ObjectHashes);
    }

    public static function serializeForRollbar(
        $obj,
        ?array $customKeys = null,
        &$objectHashes = array(),
        $maxDepth = -1,
        $depth = 0
    ) {
        
        $returnVal = array();
        
        if (is_object($obj)) {
            if (self::serializedAlready($obj, $objectHashes)) {
                return self::circularReferenceLabel($obj);
            } else {
                self::markSerialized($obj, $objectHashes);
            }
        }
        
        if ($maxDepth > 0 && $depth > $maxDepth) {
            return null;
        }

        foreach ($obj as $key => $val) {
            try {
                if (is_object($val)) {
                    $val = self::serializeObject(
                        $val,
                        $customKeys,
                        $objectHashes,
                        $maxDepth,
                        $depth
                    );
                } elseif (is_array($val)) {
                    $val = self::serializeForRollbar(
                        $val,
                        $customKeys,
                        $objectHashes,
                        $maxDepth,
                        $depth+1
                    );
                }
            } catch (\Throwable $e) {
                $val = 'Error during serialization: '.$e->getMessage();
            }

            
            if ($customKeys !== null && in_array($key, $customKeys)) {
                $returnVal[$key] = $val;
            } elseif (!is_null($val)) {
                $returnVal[$key] = $val;
            }
        }

        return $returnVal;
    }

    /**
     * Serialize the given object to an array.
     *
     * @param mixed $obj The object to serialize.
     * @return array The serialized object as an array.
     *
     * @since 4.1.2
     */
    public static function serializeToArray(mixed $obj): array
    {
        if (is_array($obj)) {
            return $obj;
        }

        // This is the most reliable way to serialize an object that does not potentially cause issues with other
        // frameworks.
        if ($obj instanceof Serializable && method_exists($obj, '__serialize')) {
            return $obj->__serialize();
        }

        if (is_object($obj)) {
            return array(
                'type' => 'object',
                'class' => get_class($obj),
            );
        }

        return array('type' => gettype($obj));
    }
    
    private static function serializeObject(
        $obj,
        ?array $customKeys = null,
        &$objectHashes = array(),
        $maxDepth = -1,
        $depth = 0
    ) {
        if (self::serializedAlready($obj, $objectHashes)) {
            return self::circularReferenceLabel($obj);
        }

        // Internal Rollbar classes.
        if ($obj instanceof SerializerInterface) {
            self::markSerialized($obj, $objectHashes);
            return $obj->serialize();
        }

        // All other classes.
        if ($obj instanceof Serializable) {
            self::markSerialized($obj, $objectHashes);
            if (method_exists($obj, '__serialize')) {
                return $obj->__serialize();
            }
            return $obj->serialize();
        }

        $serialized = array(
            'class' => get_class($obj)
        );

        // Don't serialize Iterators as rewinding them does not guarantee the
        // previous state.
        if ($obj instanceof \Iterator) {
            $serialized['value'] = 'non-serializable';
            return $serialized;
        }

        $serialized['value'] = self::serializeForRollbar(
            $obj,
            $customKeys,
            $objectHashes,
            $maxDepth,
            $depth+1
        );

        return $serialized;
    }
    
    private static function serializedAlready($obj, &$objectHashes)
    {
        if (!isset($objectHashes[spl_object_hash($obj)])) {
            return false;
        }
        
        return true;
    }
    
    private static function markSerialized($obj, &$objectHashes)
    {
        $objectHashes[spl_object_hash($obj)] = true;
        self::$ObjectHashes = $objectHashes;
    }
    
    private static function circularReferenceLabel($obj)
    {
        return '<CircularReference type:('.get_class($obj).') ref:('.spl_object_hash($obj).')>';
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
