<?php namespace Rollbar\TestHelpers\CycleCheck;

class ParentCycleCheckSerializable implements \Serializable
{
    public $child;
    
    public function __construct()
    {
        $this->child = new ChildCycleCheck($this);
    }
    
    public function serialize()
    {
        return array(
            "child" => \Rollbar\Utilities::serializeForRollbarInternal($this->child)
        );
    }
    
    public function unserialize(string $serialized)
    {
    }
}
