<?php namespace Rollbar\Handlers;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Rollbar\Payload\Level;

class ExceptionHandler extends AbstractHandler
{
    
    public function register()
    {
        $this->previousHandler = set_exception_handler(array($this, 'handle'));
        
        parent::register();
    }
    
    public function handle(
        $errno = null, 
        $errstr = null, 
        $errfile = null, 
        $errline = null
    ) {   
        
        parent::handle();
        
        $exception = $errno;
        
        $this->logger()->log(Level::ERROR, $exception, array(), true);
        if ($this->previousHandler) {
            restore_exception_handler();
            call_user_func($this->previousHandler, $exception);
            return;
        }

        throw $exception;
        
    }
    
}
