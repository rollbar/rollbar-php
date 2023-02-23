<?php declare(strict_types=1);

namespace Rollbar\Handlers;

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
    
    public function handle(...$args)
    {
        parent::handle(...$args);

        if (count($args) < 1) {
            throw new \Exception('No exception to be passed to the exception handler.');
        }
        
        $exception = $args[0];
        $this->logger()->report(Level::ERROR, $exception, isUncaught: true);

        // if there was no prior handler, then we toss that exception
        if ($this->previousHandler === null) {
            throw $exception;
        }

        // otherwise we overrode a previous handler, so restore it and call it
        restore_exception_handler();
        ($this->previousHandler)($exception);
    }
}
