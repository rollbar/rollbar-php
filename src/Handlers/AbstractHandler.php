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
        $errno = null, 
        $errstr = null, 
        $errfile = null, 
        $errline = null
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
