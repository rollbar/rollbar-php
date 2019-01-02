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
        $objectHashes = \Rollbar\Utilities::GetObjectHashes();
        return array(
            "child" => \Rollbar\Utilities::serializeForRollbar(
                $this->child,
                null,
                $objectHashes
            )
        );
    }
    
    public function unserialize($serialized)
    {
    }
}
