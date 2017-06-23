<?php namespace Rollbar\Truncation;

class StringsStrategy extends AbstractStrategy
{
    
    public static function getThresholds()
    {
        return array(1024, 512, 256);
    }
    
    public function execute(array $payload)
    {
        foreach (static::getThresholds() as $threshold) {
            if (!$this->truncation->needsTruncating($payload)) {
                break;
            }
            
            array_walk_recursive($payload, function (&$value) use ($threshold) {
                
                if (is_string($value) && strlen($value) > $threshold) {
                    $value = substr($value, 0, $threshold);
                }
            });
        }
        
        return $payload;
    }
}
