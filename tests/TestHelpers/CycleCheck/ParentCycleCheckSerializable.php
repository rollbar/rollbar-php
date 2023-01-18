<?php namespace Rollbar\TestHelpers\CycleCheck;

use Rollbar\SerializerInterface;

class ParentCycleCheckSerializable implements SerializerInterface
{
    public ChildCycleCheck $child;
    
    public function __construct()
    {
        $this->child = new ChildCycleCheck($this);
    }
    
    public function serialize(): array
    {
        return array(
            "child" => \Rollbar\Utilities::serializeForRollbarInternal($this->child)
        );
    }
}
