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

    public function handle()
    {
        /**
         * Overloading methods with different parameters is not supported in PHP
         * through language structures. This hack allows to simulate that.
         */
        $args = func_get_args();

        if (!isset($args[0]) || !isset($args[1])) {
            throw new \Exception('No $errno or $errstr to be passed to the error handler.');
        }

        $errno = $args[0];
        $errstr = $args[1];
        $errfile = isset($args[2]) ? $args[2] : null;
        $errline = isset($args[3]) ? $args[3] : null;

        parent::handle();

        if (!is_null($this->previousHandler)) {
            $stop_processing = call_user_func(
                $this->previousHandler,
                $errno,
                $errstr,
                $errfile,
                $errline
            );

            if ($stop_processing) {
                return $stop_processing;
            }
        }

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

        return false;
    }
}
