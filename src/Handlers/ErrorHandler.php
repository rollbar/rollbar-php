<?php namespace Rollbar\Handlers;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Rollbar\Payload\Level;

class ErrorHandler extends AbstractHandler
{
    
    public function register()
    {
        $this->previousHandler = set_error_handler(array($this, 'handle'));
        
        parent::register();
    }
    
    public function handle(
        $errno = null, 
        $errstr = null, 
        $errfile = null, 
        $errline = null
    ) {   
        
        parent::handle($errno, $errstr, $errfile, $errline);
        
        if (is_null($this->logger())) {
            return false;
        }
        if ($this->logger()->shouldIgnoreError($errno)) {
            return false;
        }

        $exception = $this->logger->
                            getDataBuilder()->
                            generateErrorWrapper($errno, $errstr, $errfile, $errline);

        $this->logger()->log(Level::ERROR, $exception, array(), true);
        
        if ($this->previousHandler !== null) {
            call_user_func(
                $this->previousHandler,
                $errno,
                $errstr,
                $errfile,
                $errline
            );
        }
        
        return false;
    }
    
}
