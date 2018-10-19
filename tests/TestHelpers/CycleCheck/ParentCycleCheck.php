<?php namespace Rollbar\CycleCheck;

class ParentCycleCheck
{
    public $child;
    
    public function __construct()
    {
        $this->child = new ChildCycleCheck($this);
    }
}
