<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Data;
use Stringable;
use Throwable;

/**
 * A data builder is used to normalize, parse, and prepare errors, exceptions, and messages to Rollbar.
 */
interface DataBuilderInterface
{
    /**
     * Initializes the data builder from the Rollbar configs.
     *
     * @param array $config The configuration array.
     */
    public function __construct(array $config);

    /**
     * Creates the {@see Data} object from an exception or log message. This method needs to respect the PSR-3 standard
     * on handling exceptions in the context https://www.php-fig.org/psr/psr-3/#13-context.
     *
     * @param string                      $level   The severity log level for the item being logged.
     * @param Throwable|string|Stringable $toLog   The exception or message to be logged.
     * @param array                       $context Any additional context data. This method should respect exceptions
     *                                             in the 'exception' key according PSR-3 logging standard.
     *
     * @return Data
     */
    public function makeData(string $level, Throwable|string|Stringable $toLog, array $context): Data;

    /**
     * Stores the 'custom' key from the $config array. The 'custom' key should hold an array of key / value pairs to be
     * sent to Rollbar with each request.
     *
     * @param array $config The configuration array.
     *
     * @return void
     */
    public function setCustom(array $config): void;

    /**
     * Adds a new key / value pair that will be sent with the payload to Rollbar.
     *
     * @param string $key  The key to store this value in the custom array.
     * @param mixed  $data The value that is going to be stored. Must be a primitive or JSON serializable.
     *
     * @return void
     */
    public function addCustom(string $key, mixed $data): void;

    /**
     * Removes a key from the custom data array that is sent with the payload to Rollbar.
     *
     * @param string $key The key to remove.
     *
     * @return void
     */
    public function removeCustom(string $key): void;

    /**
     * Returns the array of key / value pairs that will be sent with the payload to Rollbar.
     *
     * @return array|null
     */
    public function getCustom(): ?array;

    /**
     * Wrap a PHP error in the {@see ErrorWrapper} class and add stacktrace information.
     *
     * @param int         $errno   The level of the error raised.
     * @param string      $errstr  The error message.
     * @param string|null $errfile The filename that the error was raised in.
     * @param int|null    $errline The line number where the error was raised.
     *
     * @return ErrorWrapper
     */
    public function generateErrorWrapper(
        int $errno,
        string $errstr,
        ?string $errfile,
        ?int $errline
    ): ErrorWrapper;
}
