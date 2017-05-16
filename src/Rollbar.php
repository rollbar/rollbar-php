<?php namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\Utilities;

class Rollbar
{
    /**
     * @var RollbarLogger
     */
    private static $logger = null;
    private static $fatalErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);

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
        set_exception_handler('Rollbar\Rollbar::exceptionHandler');
    }
    
    public static function exceptionHandler($exception)
    {
        self::log(Level::error(), $exception, array(Utilities::IS_UNCAUGHT_KEY => true));
        
        restore_exception_handler();
        throw $exception;
    }

    public static function log($level, $toLog, $extra = array())
    {
        if (is_null(self::$logger)) {
            return self::getNotInitializedResponse();
        }
        return self::$logger->log($level, $toLog, (array)$extra);
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
        self::$logger->log(Level::error(), $exception, array(Utilities::IS_UNCAUGHT_KEY => true));
    }

    public static function setupFatalHandling()
    {
        register_shutdown_function('Rollbar\Rollbar::fatalHandler');
    }

    public static function fatalHandler()
    {
        if (is_null(self::$logger)) {
            return;
        }
        $last_error = error_get_last();
        if (!is_null($last_error) && in_array($last_error['type'], self::$fatalErrors, true)) {
            $errno = $last_error['type'];
            $errstr = $last_error['message'];
            $errfile = $last_error['file'];
            $errline = $last_error['line'];
            $exception = self::generateErrorWrapper($errno, $errstr, $errfile, $errline);
            $extra = array(Utilities::IS_UNCAUGHT_KEY => true);
            self::$logger->log(Level::critical(), $exception, $extra);
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
    
    // @codingStandardsIgnoreStart
    
    /**
     * Below methods are deprecated and still available only for backwards
     * compatibility. If you're still using them in your application, please
     * transition to using the ::log method as soon as possible.
     */
    
    /**
     * @param \Exception $exc Exception to be logged
     * @param array $extra_data Additional data to be logged with the exception
     * @param array $payload_data This is deprecated as of v1.0.0 and remains for
     * backwards compatibility. The content fo this array will be merged with
     * $extra_data.
     *
     * @return string uuid
     *
     * @deprecated 1.0.0 This method has been replaced by ::log
     */
    public static function report_exception($exc, $extra_data = null, $payload_data = null)
    {
        
        if ($payload_data) {
            $extra_data = array_merge($extra_data, $payload_data);
        }
        return self::log(Level::error(), $exc, $extra_data)->getUuid();
    }

    /**
     * @param string $message Message to be logged
     * @param string|Level::error() $level One of the values in \Rollbar\Payload\Level::$values
     * @param array $extra_data Additional data to be logged with the exception
     * @param array $payload_data This is deprecated as of v1.0.0 and remains for
     * backwards compatibility. The content fo this array will be merged with
     * $extra_data.
     *
     * @return string uuid
     *
     * @deprecated 1.0.0 This method has been replaced by ::log
     */
    public static function report_message($message, $level = null, $extra_data = null, $payload_data = null)
    {
        
        $level = $level ? Level::fromName($level) : Level::error();
        if ($payload_data) {
            $extra_data = array_merge($extra_data, $payload_data);
        }
        return self::log($level, $message, $extra_data)->getUuid();
    }


    /**
     * Catch any fatal errors that are causing the shutdown
     *
     * @deprecated 1.0.0 This method has been replaced by ::fatalHandler
     */
    public static function report_fatal_error()
    {
        self::fatalHandler();
    }


    /**
     * This function must return false so that the default php error handler runs
     *
     * @deprecated 1.0.0 This method has been replaced by ::log
     */
    public static function report_php_error($errno, $errstr, $errfile, $errline)
    {
        self::errorHandler($errno, $errstr, $errfile, $errline);
        return false;
    }

    /**
     * Do nothing silently to not cause backwards compatibility issues.
     *
     * @deprecated 1.0.0
     */
    public static function flush()
    {
        return;
    }
    
    // @codingStandardsIgnoreEnd
}
