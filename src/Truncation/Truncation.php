<?php namespace Rollbar\Truncation;

class Truncation
{
    const MAX_PAYLOAD_SIZE = 524288; // 512 * 1024
 
    protected static $truncationStrategies = array(
        "Rollbar\Truncation\FramesStrategy",
        "Rollbar\Truncation\StringsStrategy"
    );
    
    /**
     * Applies truncation strategies in order to keep the payload size under
     * configured limit.
     *
     * @param array $payload
     * @param string $strategy
     *
     * @return array
     */
    public function truncate(array &$payload)
    {   
        foreach (static::$truncationStrategies as $strategy) {
            if (!$this->needsTruncating($payload, $strategy)) {
                break;
            }
            
            $strategy = new $strategy($this);
            
            $payload = $strategy->execute($payload);
        }
        
        return $payload;
    }
    
    /**
     * Check if the payload is too big to be sent
     *
     * @param array $payload
     *
     * @return boolean
     */
    public function needsTruncating(array &$payload, $strategy)
    {
        $size = strlen($this->encode($payload));
        return $size > self::MAX_PAYLOAD_SIZE;
    }
    
    public function encode(array &$payload)
    {
        return json_encode($payload);
    }
}
