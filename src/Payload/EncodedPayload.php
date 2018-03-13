<?php namespace Rollbar\Payload;

class EncodedPayload
{
    protected static $encodingCount = 0;
    
    protected $data = null;
    protected $encoded = null;
    protected $size = 0;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function data()
    {
        return $this->data;
    }
    
    public function size()
    {
        return $this->size;
    }
    
    public function decreaseSize($amount)
    {
        $this->size =- $amount;
    }
    
    public function encode()
    {
        $this->encoded = json_encode($this->data);
        self::$encodingCount++;
        $this->size = strlen($this->encoded);
    }
    
    public function __toString()
    {
        return $this->encoded();
    }
    
    public function encoded()
    {
        return $this->encoded;
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
