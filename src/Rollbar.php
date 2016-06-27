<?php namespace Rollbar;

class Rollbar
{
    /**
     * @var RollbarLogger
     */
    private static $logger = null;

    public static function init(
        $config,
        $handleException = true,
        $handleError = true,
        $handleFatal = true
    ) {
        if (is_null(self::$logger)) {
            self::$logger = new RollbarLogger($config);

            if ($handleException) {
                self::setupExceptionHandling();
            }
            if ($handleError) {
                self::setupErrorHandling();
            }
            if ($handleFatal) {
                self::setupFatalHandling();
            }
        } else {
            self::$logger->configure($config);
        }
    }

    public static function logger()
    {
        return self::$logger;
    }

    public static function scope($config)
    {
        if (is_null(self::$logger)) {
            return new RollbarLogger($config);
        } else {
            return self::$logger->scope($config);
        }
    }

    public static function setupExceptionHandling()
    {
        set_exception_handler('Rollbar\Rollbar::log');
    }

    public static function log($exc, $extra = null, $level = null)
    {
        if (is_null(self::$logger)) {
            return self::getNotInitializedResponse();
        }
        return self::$logger->log($level, $exc, $extra);
    }

    public static function setupErrorHandling()
    {
        set_error_handler('Rollbar\Rollbar::errorHandler');
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (is_null(self::$logger)) {
            return;
        }
        $exception = self::generateErrorWrapper($errno, $errstr, $errfile, $errline);
        self::$logger->log(null, $exception);
    }

    public static function setupFatalHandling()
    {
        register_shutdown_function('Rollbar\Rollbar::fatalHandler');
    }

    public static function fatalHandler()
    {
        $last_error = error_get_last();
        if (!is_null($last_error)) {
            $errno = $last_error['type'];
            $errstr = $last_error['message'];
            $errfile = $last_error['file'];
            $errline = $last_error['line'];
            $exception = self::generateErrorWrapper($errno, $errstr, $errfile, $errline);
            self::$logger->log(null, $exception);
        }
    }

    private static function generateErrorWrapper($errno, $errstr, $errfile, $errline)
    {
        // removing this function and the handler function to make sure they're
        // not part of the backtrace
        $backTrace = array_slice(debug_backtrace(), 2);
        return new ErrorWrapper($errno, $errstr, $errfile, $errline, $backTrace);
    }

    private static function getNotInitializedResponse()
    {
        return new Response(0, "Rollbar Not Initialized");
    }
}
