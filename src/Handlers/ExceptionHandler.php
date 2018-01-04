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
        $arg1 = null,
        $arg2 = null,
        $arg3 = null,
        $arg4 = null,
        $arg5 = null
    ) {
        
        parent::handle($arg1, $arg2, $arg3, $arg4, $arg5);
        
        $exception = $arg1;
        
        $this->logger()->log(Level::ERROR, $exception, array(), true);
        if ($this->previousHandler) {
            restore_exception_handler();
            call_user_func($this->previousHandler, $exception);
            return;
        }

        throw $exception;
    }
}
