<?php declare(strict_types=1);

namespace Rollbar;

use Exception;
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
     * The instance of the logger. This is null if Rollbar has not been initialized or {@see Rollbar::destroy()} has
     * been called.
     *
     * @var RollbarLogger|null
     */
    private static ?RollbarLogger $logger = null;

    /**
     * The fatal error handler instance or null if it was disabled or Rollbar has not been initialized.
     *
     * @var FatalHandler|null
     */
    private static ?FatalHandler $fatalHandler = null;

    /**
     * The error handler instance or null if it was disabled or Rollbar has not been initialized.
     *
     * @var ErrorHandler|null
     */
    private static ?ErrorHandler $errorHandler = null;

    /**
     * The exception handler instance or null if it was disabled or Rollbar has not been initialized.
     *
     * @var ExceptionHandler|null
     */
    private static ?ExceptionHandler $exceptionHandler = null;

    /**
     * Sets up Rollbar monitoring and logging.
     *
     * This method may be called more than once to update or extend the configs. To do this pass an array as the
     * $configOrLogger argument. Any config values in the array will update or replace existing configs.
     *
     * Note: this and the following two parameters are only used the first time the logger is created. This prevents
     * multiple error monitors from reporting the same error more than once. To change these values you must call
     * {@see Rollbar::destroy()} first.
     *
     * Example:
     *
     *     // Turn off the fatal error handler
     *     $configs = Rollbar::logger()->getConfig();
     *     Rollbar::destroy();
     *     Rollbar::init($configs, handleFatal: false);
     *
     * @param RollbarLogger|array $configOrLogger  This can be either an array of config options or an already
     *                                             configured {@see RollbarLogger} instance.
     * @param bool                $handleException If set to false Rollbar will not monitor exceptions.
     * @param bool                $handleError     If set to false Rollbar will not monitor errors.
     * @param bool                $handleFatal     If set to false Rollbar will not monitor fatal errors.
     *
     * @return void
     * @throws Exception If the $configOrLogger argument is an array that has invalid configs.
     *
     * @link https://docs.rollbar.com/docs/basic-php-installation-setup
     */
    public static function init(
        RollbarLogger|array $configOrLogger,
        bool $handleException = true,
        bool $handleError = true,
        bool $handleFatal = true
    ): void {
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

    /**
     * Creates or configures the RollbarLogger instance.
     *
     * @param RollbarLogger|array $configOrLogger The configs array or a new {@see RollbarLogger} to use or replace the
     *                                            current one with, if one already exists. If a logger already exists
     *                                            and this is an array the current logger's configs will be extended
     *                                            configs from the array.
     *
     * @return void
     * @throws Exception If the $configOrLogger argument is an array that has invalid configs.
     */
    private static function setLogger(RollbarLogger|array $configOrLogger): void
    {
        if ($configOrLogger instanceof RollbarLogger) {
            self::$logger = $configOrLogger;
            return;
        }

        // Replacing the logger rather than configuring the existing logger breaks BC
        if (self::$logger !== null) {
            self::$logger->configure($configOrLogger);
            return;
        }

        self::$logger = new RollbarLogger($configOrLogger);
    }

    /**
     * Enables logging of errors to Rollbar.
     *
     * @return void
     */
    public static function enable(): void
    {
        self::logger()->enable();
    }

    /**
     * Disables logging of errors to Rollbar.
     *
     * @return void
     */
    public static function disable(): void
    {
        self::logger()->disable();
    }

    /**
     * Returns true if the Rollbar logger is enabled.
     *
     * @return bool
     */
    public static function enabled(): bool
    {
        return self::logger()->enabled();
    }

    /**
     * Returns true if the Rollbar logger is disabled.
     *
     * @return bool
     */
    public static function disabled(): bool
    {
        return self::logger()->disabled();
    }

    /**
     * Returns the current logger instance, or null if it has not been initialized or has been destroyed.
     *
     * @return RollbarLogger|null
     */
    public static function logger(): ?RollbarLogger
    {
        return self::$logger;
    }

    /**
     * Creates and returns a new {@see RollbarLogger} instance.
     *
     * @param array $config The configs extend the configs of the current logger instance, if it exists.
     *
     * @return RollbarLogger
     * @throws Exception If the $config argument is an array that has invalid configs.
     */
    public static function scope(array $config): RollbarLogger
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
     * Attempts to log a {@see Throwable} as an uncaught exception.
     *
     * @param string|Level $level   The log level severity to use.
     * @param Throwable    $toLog   The exception to log.
     * @param array        $context The array of additional data to pass with the stack trace.
     *
     * @return Response
     * @throws Throwable The rethrown $toLog.
     *
     * @since 3.0.0
     */
    public static function logUncaught(string|Level $level, Throwable $toLog, array $context = array()): Response
    {
        if (is_null(self::$logger)) {
            return self::getNotInitializedResponse();
        }
        return self::$logger->report($level, $toLog, $context, isUncaught: true);
    }

    /**
     * Logs a message with the {@see Level::DEBUG} log level.
     *
     * @param string|Stringable $message The debug message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function debug(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::DEBUG, $message, $context);
    }

    /**
     * Logs a message with the {@see Level::INFO} log level.
     *
     * @param string|Stringable $message The info message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function info(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::INFO, $message, $context);
    }

    /**
     * Logs a message with the {@see Level::NOTICE} log level.
     *
     * @param string|Stringable $message The notice message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function notice(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::NOTICE, $message, $context);
    }

    /**
     * Logs a message with the {@see Level::WARNING} log level.
     *
     * @param string|Stringable $message The warning message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function warning(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::WARNING, $message, $context);
    }

    /**
     * Logs a message with the {@see Level::ERROR} log level.
     *
     * @param string|Stringable $message The error message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function error(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::ERROR, $message, $context);
    }

    /**
     * Logs a message with the {@see Level::CRITICAL} log level.
     *
     * @param string|Stringable $message The critical message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function critical(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::CRITICAL, $message, $context);
    }

    /**
     * Logs a message with the {@see Level::ALERT} log level.
     *
     * @param string|Stringable $message The alert message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function alert(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::ALERT, $message, $context);
    }

    /**
     * Logs a message with the {@see Level::EMERGENCY} log level.
     *
     * @param string|Stringable $message The emergency message to log.
     * @param array             $context The additional data to send with the message.
     *
     * @return void
     * @throws Throwable
     */
    public static function emergency(string|Stringable $message, array $context = array()): void
    {
        self::log(Level::EMERGENCY, $message, $context);
    }

    /**
     * Creates a listener that monitors for exceptions.
     *
     * @return void
     */
    public static function setupExceptionHandling(): void
    {
        self::$exceptionHandler = new ExceptionHandler(self::$logger);
        self::$exceptionHandler->register();
    }

    /**
     * Creates a listener that monitors for errors.
     *
     * @return void
     */
    public static function setupErrorHandling(): void
    {
        self::$errorHandler = new ErrorHandler(self::$logger);
        self::$errorHandler->register();
    }

    /**
     * Creates a listener that monitors for fatal errors that cause the program to shut down.
     *
     * @return void
     */
    public static function setupFatalHandling(): void
    {
        self::$fatalHandler = new FatalHandler(self::$logger);
        self::$fatalHandler->register();
    }

    /**
     * Creates and returns a {@see Response} to use if Rollbar is attempted to be used prior to being initialized.
     *
     * @return Response
     */
    private static function getNotInitializedResponse(): Response
    {
        return new Response(0, "Rollbar Not Initialized");
    }

    /**
     * This method makes sure the queue of logs stored in memory are sent to Rollbar prior to shut down.
     *
     * @return void
     */
    public static function setupBatchHandling(): void
    {
        register_shutdown_function('Rollbar\Rollbar::flushAndWait');
    }

    /**
     * Sends all the queued logs to Rollbar.
     *
     * @return void
     */
    public static function flush(): void
    {
        if (is_null(self::$logger)) {
            return;
        }
        self::$logger->flush();
    }

    /**
     * Sends all the queued logs to Rollbar and waits for the response.
     *
     * @return void
     */
    public static function flushAndWait(): void
    {
        if (is_null(self::$logger)) {
            return;
        }
        self::$logger->flushAndWait();
    }

    /**
     * Adds a new key / value pair that will be sent with the payload to Rollbar. If the key already exists in the
     * custom data array the existing value will be overwritten.
     *
     * @param string $key  The key to store this value in the custom array.
     * @param mixed  $data The value that is going to be stored. Must be a primitive or JSON serializable.
     *
     * @return void
     */
    public static function addCustom(string $key, mixed $data): void
    {
        self::$logger->addCustom($key, $data);
    }

    /**
     * Removes a key from the custom data array that is sent with the payload to Rollbar.
     *
     * @param string $key The key to remove.
     *
     * @return void
     */
    public static function removeCustom(string $key): void
    {
        self::$logger->removeCustom($key);
    }

    /**
     * Returns the array of key / value pairs that will be sent with the payload to Rollbar.
     *
     * @return array|null
     */
    public static function getCustom(): ?array
    {
        return self::$logger->getCustom();
    }

    /**
     * Configures the existing {@see RollbarLogger} instance.
     *
     * @param array $config The array of configs. This does not need to be complete as it extends the existing
     *                      configuration. Any existing values present in the new configs will be overwritten.
     *
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$logger->configure($config);
    }

    /**
     * Destroys the currently stored $logger allowing for a fresh configuration. This is especially used in testing
     * scenarios.
     *
     * @return void
     */
    public static function destroy(): void
    {
        self::$logger = null;
    }
}
