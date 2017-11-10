<?php namespace Rollbar;

use Rollbar\Payload\Level;

class Rollbar
{
    /**
     * @var RollbarLogger
     */
    private static $logger = null;
    private static $previousExceptionHandler = null;
    private static $fatalErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);

    public static function init(
        $configOrLogger,
        $handleException = true,
        $handleError = true,
        $handleFatal = true
    ) {
        $setupHandlers = is_null(self::$logger);

        self::setLogger($configOrLogger);

        if ($setupHandlers) {
            if ($handleException) {
                self::setupExceptionHandling();
            }
            if ($handleError) {
                self::setupErrorHandling();
            }
            if ($handleFatal) {
                self::setupFatalHandling();
            }
            self::setupBatchHandling();
        }
    }

    private static function setLogger($configOrLogger)
    {
        if ($configOrLogger instanceof RollbarLogger) {
            $logger = $configOrLogger;
        }

        // Replacing the logger rather than configuring the existing logger breaks BC
        if (self::$logger && !isset($logger)) {
            self::$logger->configure($configOrLogger);
            return;
        }

        self::$logger = isset($logger) ? $logger : new RollbarLogger($configOrLogger);
    }

    public static function logger()
    {
        return self::$logger;
    }

    public static function scope($config)
    {
        if (is_null(self::$logger)) {
            return new RollbarLogger($config);
        }
        return self::$logger->scope($config);
    }

    public static function setupExceptionHandling()
    {
        self::$previousExceptionHandler = set_exception_handler('Rollbar\Rollbar::exceptionHandler');
    }
    
    public static function exceptionHandler($exception)
    {
        self::log(Level::ERROR, $exception, array(), true);
        if (self::$previousExceptionHandler) {
            restore_exception_handler();
            call_user_func(self::$previousExceptionHandler, $exception);
            return;
        }

        throw $exception;
    }

    public static function log($level, $toLog, $extra = array())
    {
        if (is_null(self::$logger)) {
            return self::getNotInitializedResponse();
        }
        return self::$logger->log($level, $toLog, (array)$extra);
    }
    
    public static function debug($toLog, $extra = array())
    {
        self::log(Level::DEBUG, $toLog, $extra);
    }
    
    public static function info($toLog, $extra = array())
    {
        self::log(Level::INFO, $toLog, $extra);
    }
    
    public static function notice($toLog, $extra = array())
    {
        self::log(Level::NOTICE, $toLog, $extra);
    }
    
    public static function warning($toLog, $extra = array())
    {
        self::log(Level::WARNING, $toLog, $extra);
    }
    
    public static function error($toLog, $extra = array())
    {
        self::log(Level::ERROR, $toLog, $extra);
    }
    
    public static function critical($toLog, $extra = array())
    {
        self::log(Level::CRITICAL, $toLog, $extra);
    }
    
    public static function alert($toLog, $extra = array())
    {
        self::log(Level::ALERT, $toLog, $extra);
    }
    
    public static function emergency($toLog, $extra = array())
    {
        self::log(Level::EMERGENCY, $toLog, $extra);
    }
    
    public static function setupErrorHandling()
    {
        set_error_handler('Rollbar\Rollbar::errorHandler');
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (is_null(self::$logger)) {
            return false;
        }
        if (self::$logger->shouldIgnoreError($errno)) {
            return false;
        }

        $exception = self::generateErrorWrapper($errno, $errstr, $errfile, $errline);
        self::$logger->log(Level::ERROR, $exception, array(), true);
        return false;
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
        
        if (self::shouldLogFatal($last_error)) {
            $errno = $last_error['type'];
            $errstr = $last_error['message'];
            $errfile = $last_error['file'];
            $errline = $last_error['line'];
            $exception = self::generateErrorWrapper($errno, $errstr, $errfile, $errline);
            self::$logger->log(Level::CRITICAL, $exception, array(), true);
        }
    }
    
    protected static function shouldLogFatal($last_error)
    {
        return
            !is_null($last_error) &&
            in_array($last_error['type'], self::$fatalErrors, true) &&
            // don't log uncaught exceptions as they were handled by exceptionHandler()
            !(isset($last_error['message']) &&
              strpos($last_error['message'], 'Uncaught exception') === 0);
    }

    private static function generateErrorWrapper($errno, $errstr, $errfile, $errline)
    {
        if (null === self::$logger) {
            return;
        }
        
        $dataBuilder = self::$logger->getDataBuilder();
        
        return $dataBuilder->generateErrorWrapper($errno, $errstr, $errfile, $errline);
    }

    private static function getNotInitializedResponse()
    {
        return new Response(0, "Rollbar Not Initialized");
    }
    
    public static function setupBatchHandling()
    {
        register_shutdown_function('Rollbar\Rollbar::flushAndWait');
    }

    public static function flush()
    {
        if (is_null(self::$logger)) {
            return;
        }
        self::$logger->flush();
    }

    public static function flushAndWait()
    {
        if (is_null(self::$logger)) {
            return;
        }
        self::$logger->flushAndWait();
    }
    
    public static function addCustom($key, $value)
    {
        self::$logger->addCustom($key, $value);
    }
    
    public static function removeCustom($key)
    {
        self::$logger->removeCustom($key);
    }
    
    public static function getCustom()
    {
        self::$logger->getCustom();
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
        return self::log(Level::ERROR, $exc, $extra_data)->getUuid();
    }

    /**
     * @param string $message Message to be logged
     * @param string $level One of the values in \Rollbar\Payload\Level::$values
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
        
        $level = $level ? $level : Level::ERROR;
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

    // @codingStandardsIgnoreEnd
}
