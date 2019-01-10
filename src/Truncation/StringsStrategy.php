<?php namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

class StringsStrategy extends AbstractStrategy
{
    
    public static function getThresholds()
    {
        return array(1024, 512, 256);
    }
    
    public function execute(EncodedPayload $payload)
    {
        $data = $payload->data();
        $modified = false;
        
        foreach (static::getThresholds() as $threshold) {
            $maxPayloadSize = \Rollbar\Truncation\Truncation::MAX_PAYLOAD_SIZE;
            
            if (!$this->truncation->needsTruncating($payload, $this)) {
                break;
            }
            
            if ($this->traverse($data, $threshold, $payload)) {
                $modified = true;
            }
        }
        
        if ($modified) {
            $payload->encode($data);
        }
        
        return $payload;
    }
    
    protected function traverse(&$data, $threshold, $payload)
    {
        $modified = false;
        
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                if ($this->traverse($value, $threshold, $payload)) {
                    $modified = true;
                }
            } else {
                if (is_string($value) && (($strlen = strlen($value)) > $threshold)) {
                    $value = substr($value, 0, $threshold);
                    $modified = true;
                    $payload->decreaseSize($strlen - $threshold);
                }
            }
        }
        
        return $modified;
    }
}
