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
        $arg1 = null,
        $arg2 = null,
        $arg3 = null,
        $arg4 = null,
        $arg5 = null
    ) {
        
        parent::handle($arg1, $arg2, $arg3, $arg4, $arg5);
        
        $errno = $arg1;
        $errstr = $arg2;
        $errfile = $arg3;
        $errline = $arg4;
        
        if (is_null($this->logger())) {
            return false;
        }
        if ($this->logger()->shouldIgnoreError($errno)) {
            return false;
        }

        $exception = $this->logger()->
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
