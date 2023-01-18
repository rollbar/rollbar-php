<?php namespace Rollbar\Performance\TestHelpers;

class EncodedPayload extends \Rollbar\Payload\EncodedPayload
{
    protected static int $encodingCount = 0;
    
    public function encode(?array $data = null): void
    {
        parent::encode($data);
        self::$encodingCount++;
    }
    
    public static function getEncodingCount(): int
    {
        return self::$encodingCount;
    }
    
    public static function resetEncodingCount(): void
    {
        self::$encodingCount = 0;
    }
}
