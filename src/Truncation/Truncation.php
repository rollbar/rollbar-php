<?php namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

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
     * @param \Rollbar\Payload\EncodedPayload $payload
     * @param string $strategy
     *
     * @return \Rollbar\Payload\EncodedPayload
     */
    public function truncate(EncodedPayload $payload)
    {
        foreach (static::$truncationStrategies as $strategy) {
            $strategy = new $strategy($this);
            
            if (!$strategy->applies($payload)) {
                continue;
            }
            
            if (!$this->needsTruncating($payload, $strategy)) {
                break;
            }
    
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
    public function needsTruncating(EncodedPayload $payload, $strategy)
    {
        return $payload->size() > self::MAX_PAYLOAD_SIZE;
    }
}
