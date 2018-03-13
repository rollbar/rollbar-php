<?php namespace Rollbar\Performance\TestHelpers;

class EncodedPayload extends \Rollbar\Payload\EncodedPayload
{
    protected static $encodingCount = 0;
    
    public function encode($data = null)
    {
        parent::encode($data);
        self::$encodingCount++;
    }
    
    public static function getEncodingCount()
    {
        return self::$encodingCount;
    }
    
    public static function resetEncodingCount()
    {
        self::$encodingCount = 0;
    }
}
