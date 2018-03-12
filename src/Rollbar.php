<?php namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\Handlers\FatalHandler;
use Rollbar\Handlers\ErrorHandler;
use Rollbar\Handlers\ExceptionHandler;

class Rollbar
{
    /**
     * @var RollbarLogger
     */
    private static $logger = null;
    private static $fatalHandler = null;
    private static $errorHandler = null;
    private static $exceptionHandler = null;

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
    
    public static function enable()
    {
        return self::logger()->enable();
    }
    
    public static function disable()
    {
        return self::logger()->disable();
    }
    
    public static function enabled()
    {
        return self::logger()->enabled();
    }
    
    public static function disabled()
    {
        return self::logger()->disabled();
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

    public static function log($level, $toLog, $extra = array(), $isUncaught = false)
    {
        if (is_null(self::$logger)) {
            return self::getNotInitializedResponse();
        }
        return self::$logger->log($level, $toLog, (array)$extra, $isUncaught);
    }
    
    public static function debug($toLog, $extra = array())
    {
        return self::log(Level::DEBUG, $toLog, $extra);
    }
    
    public static function info($toLog, $extra = array())
    {
        return self::log(Level::INFO, $toLog, $extra);
    }
    
    public static function notice($toLog, $extra = array())
    {
        return self::log(Level::NOTICE, $toLog, $extra);
    }
    
    public static function warning($toLog, $extra = array())
    {
        return self::log(Level::WARNING, $toLog, $extra);
    }
    
    public static function error($toLog, $extra = array())
    {
        return self::log(Level::ERROR, $toLog, $extra);
    }
    
    public static function critical($toLog, $extra = array())
    {
        return self::log(Level::CRITICAL, $toLog, $extra);
    }
    
    public static function alert($toLog, $extra = array())
    {
        return self::log(Level::ALERT, $toLog, $extra);
    }
    
    public static function emergency($toLog, $extra = array())
    {
        return self::log(Level::EMERGENCY, $toLog, $extra);
    }

    public static function setupExceptionHandling()
    {
        self::$exceptionHandler = new ExceptionHandler(self::$logger);
        self::$exceptionHandler->register();
    }
    
    public static function setupErrorHandling()
    {
        self::$errorHandler = new ErrorHandler(self::$logger);
        self::$errorHandler->register();
    }

    public static function setupFatalHandling()
    {
        self::$fatalHandler = new FatalHandler(self::$logger);
        self::$fatalHandler->register();
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
    
    public static function configure($config)
    {
        self::$logger->configure($config);
    }
    
    /**
     * Destroys the currently stored $logger allowing for a fresh configuration.
     * This is especially used in testing scenarios.
     */
    public static function destroy()
    {
        self::$logger = null;
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
        self::$fatalHandler->handle();
    }


    /**
     * This function must return false so that the default php error handler runs
     *
     * @deprecated 1.0.0 This method has been replaced by ::log
     */
    public static function report_php_error($errno, $errstr, $errfile, $errline)
    {
        self::$errorHandler->handle($errno, $errstr, $errfile, $errline);
        return false;
    }

    // @codingStandardsIgnoreEnd
}
