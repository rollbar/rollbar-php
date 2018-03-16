<?php namespace Rollbar\Payload;

class EncodedPayload
{
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
    
    public function encode($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        }
        
        $this->encoded = json_encode($this->data, JSON_PARTIAL_OUTPUT_ON_ERROR);
        
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
}
