<?php namespace Rollbar\TestHelpers\CycleCheck;

use Rollbar\SerializerInterface;

class ParentCycleCheckSerializable implements SerializerInterface
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
}
