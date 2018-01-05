<?php namespace Rollbar\Handlers;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Rollbar\Payload\Level;

/**
 * Previously registered shutdown functions will be called automatically by PHP.
 * There is no need to invoke them manually, unline with ErrorHandler class
 * and set_error_handler function.
 */
class FatalHandler extends AbstractHandler
{
    
    private static $fatalErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
    
    public function register()
    {
        \register_shutdown_function(array($this, 'handle'));
        
        parent::register();
    }
    
    public function handle()
    {
        
        parent::handle();
        
        if (is_null($this->logger())) {
            return;
        }
        $lastError = error_get_last();
        
        if ($this->isFatal($lastError)) {
            $errno = $lastError['type'];
            $errstr = $lastError['message'];
            $errfile = $lastError['file'];
            $errline = $lastError['line'];
            
            $exception = $this->logger()->
                                getDataBuilder()->
                                generateErrorWrapper($errno, $errstr, $errfile, $errline);
                                
            $this->logger()->log(Level::CRITICAL, $exception, array(), true);
        }
    }
    
    /**
     * Check if the error triggered is indeed a fatal error.
     *
     * @var array $lastError Information fetched from error_get_last().
     *
     * @return bool
     */
    protected function isFatal($lastError)
    {
        return
            !is_null($lastError) &&
            in_array($lastError['type'], self::$fatalErrors, true) &&
            // don't log uncaught exceptions as they were handled by exceptionHandler()
            !(isset($lastError['message']) &&
              strpos($lastError['message'], 'Uncaught') === 0);
    }
}
