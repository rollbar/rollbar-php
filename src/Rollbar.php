<?php declare(strict_types=1);

namespace Rollbar;

use Psr\Log\InvalidArgumentException;
use Rollbar\Payload\Level;
use Rollbar\Handlers\FatalHandler;
use Rollbar\Handlers\ErrorHandler;
use Rollbar\Handlers\ExceptionHandler;
use Stringable;
use Throwable;

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

        self::$logger = $logger ?? new RollbarLogger($configOrLogger);
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

    /**
     * Logs a message to the Rollbar service with the specified level.
     *
     * @param Level|string      $level   The severity level of the message.
     *                                   Must be one of the levels as defined in
     *                                   the {@see Level} constants.
     * @param string|Stringable $message The log message.
     * @param array             $context Arbitrary data.
     *
     * @return void
     *
     * @throws InvalidArgumentException If $level is not a valid level.
     * @throws Throwable Rethrown $message if it is {@see Throwable} and {@see Config::raiseOnError} is true.
     */
    public static function log($level, string|Stringable $message, array $context = array()): void
    {
        if (is_null(self::$logger)) {
            return;
        }
        self::$logger->log($level, $message, $context);
    }

    /**
     * Creates the {@see Response} object and reports the message to the Rollbar
     * service.
     *
     * @param string|Level      $level   The severity level to send to Rollbar.
     * @param string|Stringable $message The log message.
     * @param array             $context Any additional context data.
     *
     * @return Response
     *
     * @throws InvalidArgumentException If $level is not a valid level.
     * @throws Throwable Rethrown $message if it is {@see Throwable} and {@see Config::raiseOnError} is true.
     *
     * @since 4.0.0
     */
    public static function report($level, string|Stringable $message, array $context = array()): Response
    {
        if (is_null(self::$logger)) {
            return self::getNotInitializedResponse();
        }
        return self::$logger->report($level, $message, $context);
    }

    /**
     * @since 3.0.0
     */
    public static function logUncaught($level, Throwable $toLog, $extra = array())
    {
        if (is_null(self::$logger)) {
            return self::getNotInitializedResponse();
        }
        $toLog->isUncaught = true;
        try {
            $result = self::$logger->report($level, $toLog, (array)$extra);
        } finally {
            unset($toLog->isUncaught);
        }
        return $result;
    }
    
    public static function debug(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::DEBUG, $message, $context);
    }
    
    public static function info(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::INFO, $message, $context);
    }
    
    public static function notice(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::NOTICE, $message, $context);
    }
    
    public static function warning(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::WARNING, $message, $context);
    }
    
    public static function error(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::ERROR, $message, $context);
    }
    
    public static function critical(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::CRITICAL, $message, $context);
    }
    
    public static function alert(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::ALERT, $message, $context);
    }
    
    public static function emergency(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::EMERGENCY, $message, $context);
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

    private static function getNotInitializedResponse(): Response
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
        $extra_data = array_merge($extra_data ?? [], $payload_data ?? []);
        return self::report(Level::ERROR, $exc, $extra_data)->getUuid();
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
        $level = $level ?? Level::ERROR;
        $extra_data = array_merge($extra_data ?? [], $payload_data ?? []);
        return self::report($level, $message, $extra_data)->getUuid();
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
