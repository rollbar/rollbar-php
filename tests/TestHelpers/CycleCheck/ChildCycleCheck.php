<?php namespace Rollbar\CycleCheck;

class ChildCycleCheck
{
    public $parent;
    
    public function __construct($parent)
    {
        $this->parent = $parent;
    }
}
