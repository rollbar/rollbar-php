<?php namespace Rollbar\TestHelpers\ChildCycle;

class ChildCycleCheckSerializable implements \Serializable
{
    public $parent;
    
    public function __construct($parent)
    {
        $this->parent = $parent;
    }
    
    public function serialize()
    {
        return array(
            "parent" => \Rollbar\Utilities::serializeForRollbarInternal($this->parent)
        );
    }
    
    public function unserialize(string $serialized)
    {
    }
}
