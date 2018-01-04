<?php namespace Rollbar\Handlers;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Rollbar\Payload\Level;

abstract class AbstractHandler
{
    protected $registered = false;
    
    protected $logger = null;
    
    protected $previousHandler = null;
    
    public function __construct(RollbarLogger $logger)
    {
        $this->logger = $logger;
    }
    
    public function logger()
    {
        return $this->logger;
    }
    
    public function registered()
    {
        return $this->registered;
    }
    
    public function handle(
        $arg1 = null,
        $arg2 = null,
        $arg3 = null,
        $arg4 = null,
        $arg5 = null
    ) {
        if (!$this->registered()) {
            throw new \Exception(static::class . ' has not been set up.');
        }
    }
    
    public function register()
    {
        $this->registered = true;
    }
}
