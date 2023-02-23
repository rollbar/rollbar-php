<?php declare(strict_types=1);

namespace Rollbar\Handlers;

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

    public function handle(...$args)
    {
        parent::handle(...$args);

        if (count($args) < 2) {
            throw new \Exception('No $errno or $errstr to be passed to the error handler.');
        }

        $errno = $args[0];
        $errstr = $args[1];
        $errfile = $args[2] ?: null;
        $errline = $args[3] ?: null;

        if ($this->previousHandler) {
            $stop_processing = ($this->previousHandler)($errno, $errstr, $errfile, $errline);
            if ($stop_processing) {
                return $stop_processing;
            }
        }

        if ($this->logger()->shouldIgnoreError($errno)) {
            return false;
        }

        $exception = $this->logger()->
                            getDataBuilder()->
                            generateErrorWrapper($errno, $errstr, $errfile, $errline);
        $this->logger()->report(Level::ERROR, $exception, isUncaught: true);

        return false;
    }
}
