<?php declare(strict_types=1);

namespace Rollbar;

use Exception;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stringable;
use Throwable;
use Psr\Log\AbstractLogger;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;
use Rollbar\Truncation\Truncation;
use Rollbar\Payload\EncodedPayload;

class RollbarLogger extends AbstractLogger
{
    /**
     * @var Config $config The logger configuration instance.
     */
    private Config $config;

    /**
     * @var Truncation The payload truncation manager for the logger.
     */
    private Truncation $truncation;

    /**
     * @var array $queue The queue for sending payloads in batched mode.
     */
    private array $queue;

    /**
     * @var int $reportCount The number of reports already sent or queued.
     */
    private int $reportCount = 0;

    /**
     * Creates a new instance of the RollbarLogger.
     *
     * @param array $config The array of configs to use for the logger.
     *
     * @throws Exception If a custom truncation strategy class does not implement {@see StrategyInterface}.
     */
    public function __construct(array $config)
    {
        $this->config     = new Config($config);
        $this->truncation = new Truncation($this->config);
        $this->queue      = array();
    }

    /**
     * Returns the configs object.
     *
     * @return Config
     *
     * @since 3.0
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Enables logging of errors to Rollbar.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->config->enable();
    }

    /**
     * Disables logging of errors to Rollbar.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->config->disable();
    }

    /**
     * Returns true if the Rollbar logger is enabled.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->config->enabled();
    }

    /**
     * Returns true if the Rollbar logger is disabled.
     *
     * @return bool
     */
    public function disabled(): bool
    {
        return $this->config->disabled();
    }

    /**
     * Updates the existing configurations. All existing configurations will be kept unless explicitly updated in the
     * $config array.
     *
     * @param array $config The new configurations to add or overwrite the existing configurations.
     *
     * @return void
     */
    public function configure(array $config): void
    {
        $this->config->configure($config);
    }

    /**
     * Returns a new {@see RollbarLogger} instance with a combination of the configs from the current logger updated
     * with the extra $configs array.
     *
     * @param array $config Additional configurations to update or overwrite the existing configurations.
     *
     * @return RollbarLogger
     * @throws Exception
     */
    public function scope(array $config): RollbarLogger
    {
        return new RollbarLogger($this->extend($config));
    }

    /**
     * Deeply combines the provided $config array and the existing configurations and returns the combined array of
     * configs.
     *
     * @param array $config The new configs to update or overwrite the existing configurations.
     *
     * @return array
     */
    public function extend(array $config): array
    {
        return $this->config->extend($config);
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
    public function addCustom(string $key, mixed $data): void
    {
        $this->config->addCustom($key, $data);
    }

    /**
     * Removes a key from the custom data array that is sent with the payload to Rollbar.
     *
     * @param string $key The key to remove.
     *
     * @return void
     */
    public function removeCustom(string $key): void
    {
        $this->config->removeCustom($key);
    }

    /**
     * Returns the array of key / value pairs that will be sent with the payload to Rollbar.
     *
     * @return array|null
     */
    public function getCustom(): mixed
    {
        return $this->config->getCustom();
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
    public function log($level, $message, array $context = array()): void
    {
        $this->report($level, $message, $context);
    }

    /**
     * Creates the {@see Response} object and reports the message to the Rollbar
     * service.
     *
     * @param string|Level      $level      The severity level to send to Rollbar.
     * @param string|Stringable $message    The log message.
     * @param array             $context    Any additional context data.
     * @param bool              $isUncaught True if the error or exception was captured by a Rollbar handler. Thus, it
     *                                      was not caught by the application.
     *
     * @return Response
     *
     * @throws InvalidArgumentException If $level is not a valid level.
     * @throws Throwable Rethrown $message if it is {@see Throwable} and {@see Config::raiseOnError} is true.
     *
     * @since  4.0.0
     */
    public function report(
        string|Level $level,
        string|Stringable $message,
        array $context = array(),
        bool $isUncaught = false
    ): Response {
        if ($this->disabled()) {
            $this->verboseLogger()->notice('Rollbar is disabled');
            return new Response(0, "Disabled");
        }

        // Convert a Level proper into a string proper, as the code paths that
        // follow have allowed both only by virtue that a Level downcasts to a
        // string. With strict types, that no longer happens. We should consider
        // tightening the boundary so that we convert from string to Level
        // enum here, and work with Level enum through protected level.
        if ($level instanceof Level) {
            $level = (string)$level;
        } elseif (!LevelFactory::isValidLevel($level)) {
            $exception = new InvalidArgumentException("Invalid log level '$level'.");
            $this->verboseLogger()->error($exception->getMessage());
            throw $exception;
        }

        $this->verboseLogger()->info("Attempting to log: [$level] " . $message);

        if ($this->config->internalCheckIgnored($level, $message)) {
            $this->verboseLogger()->info('Occurrence ignored');
            return new Response(0, "Ignored");
        }

        $accessToken = $this->getAccessToken();
        $payload     = $this->getPayload($accessToken, $level, $message, $context);

        if ($this->config->checkIgnored($payload, $message, $isUncaught)) {
            $this->verboseLogger()->info('Occurrence ignored');
            $response = new Response(0, "Ignored");
        } else {
            $serialized = $payload->serialize($this->config->getMaxNestingDepth());

            $scrubbed = $this->scrub($serialized);

            $encoded = $this->encode($scrubbed);

            $truncated = $this->truncate($encoded);

            $response = $this->send($truncated, $accessToken);
        }

        $this->handleResponse($payload, $response);

        if ($response->getStatus() === 0) {
            $this->verboseLogger()->error('Occurrence rejected by the SDK: ' . $response);
        } elseif ($response->getStatus() >= 400) {
            $info = $response->getInfo();
            $this->verboseLogger()->error(
                'Occurrence rejected by the API: with status ' . $response->getStatus() . ': '
                . ($info['message'] ?? 'message not set')
            );
        } else {
            $this->verboseLogger()->info('Occurrence successfully logged');
        }

        if ($message instanceof Throwable && $this->config->getRaiseOnError()) {
            throw $message;
        }

        return $response;
    }

    /**
     * Sends and flushes the batch payload queue.
     *
     * @return Response|null
     */
    public function flush(): ?Response
    {
        if ($this->getQueueSize() > 0) {
            $batch       = $this->queue;
            $this->queue = array();
            return $this->config->sendBatch($batch, $this->getAccessToken());
        }
        $this->verboseLogger()->debug('Queue flushed');
        return new Response(0, "Queue empty");
    }

    /**
     * Sends and flushes the batch payload queue, and waits for the requests to be sent.
     *
     * @return void
     */
    public function flushAndWait(): void
    {
        $this->flush();
        $this->config->wait($this->getAccessToken());
    }

    /**
     * Returns true if the error level should be ignored because of error level or sampling rates.
     *
     * @param int $errno The PHP error level bitmask.
     *
     * @return bool
     */
    public function shouldIgnoreError(int $errno): bool
    {
        return $this->config->shouldIgnoreError($errno);
    }

    /**
     * Returns the number of report payloads currently in batch queue.
     *
     * @return int
     */
    public function getQueueSize(): int
    {
        return count($this->queue);
    }

    /**
     * Sends a report to the Rollbar service.
     *
     * @param EncodedPayload $payload     The prepared payload to send.
     * @param string         $accessToken The API access token.
     *
     * @return Response
     */
    protected function send(EncodedPayload $payload, string $accessToken): Response
    {
        if ($this->reportCount >= $this->config->getMaxItems()) {
            $response = new Response(
                0,
                "Maximum number of items per request has been reached. If you " .
                "want to report more items, please use `max_items` " .
                "configuration option."
            );
            $this->verboseLogger()->warning($response->getInfo());
            return $response;
        } else {
            $this->reportCount++;
        }

        if ($this->config->getBatched()) {
            $response = new Response(0, "Pending");
            if ($this->getQueueSize() >= $this->config->getBatchSize()) {
                $response = $this->flush();
            }
            $this->queue[] = $payload;
            $this->verboseLogger()->debug("Added payload to the queue (running in `batched` mode).");
            return $response;
        }

        return $this->config->send($payload, $accessToken);
    }

    /**
     * Creates a payload from a log level and message or error.
     *
     * @param string            $accessToken The API access token.
     * @param string            $level       The log level. Should be one of the {@see Level} constants.
     * @param string|Stringable $toLog       The message or error to be sent to Rollbar.
     * @param array             $context     Additional data to send with the message.
     *
     * @return Payload
     */
    protected function getPayload(
        string $accessToken,
        string $level,
        string|Stringable $toLog,
        array $context,
    ): Payload {
        $data    = $this->config->getRollbarData($level, $toLog, $context);
        $payload = new Payload($data, $accessToken);
        return $this->config->transform($payload, $level, $toLog, $context);
    }

    /**
     * Returns the access token for the logger instance.
     *
     * @return string
     */
    protected function getAccessToken(): string
    {
        return $this->config->getAccessToken();
    }

    /**
     * Returns the configured DataBuilder instance.
     *
     * @return DataBuilder
     */
    public function getDataBuilder(): DataBuilder
    {
        return $this->config->getDataBuilder();
    }

    /**
     * Returns the logger responsible for logging request payload and response dumps, if enabled.
     *
     * @return LoggerInterface
     */
    public function logPayloadLogger(): LoggerInterface
    {
        return $this->config->logPayloadLogger();
    }

    /**
     * Returns the verbose logger instance.
     *
     * @return LoggerInterface
     */
    public function verboseLogger(): LoggerInterface
    {
        return $this->config->verboseLogger();
    }

    /**
     * Calls the custom 'responseHandler', config if it exists.
     *
     * @param Payload  $payload  The payload that was sent.
     * @param Response $response The response from the Rollbar service.
     *
     * @return void
     */
    protected function handleResponse(Payload $payload, Response $response): void
    {
        $this->config->handleResponse($payload, $response);
    }

    /**
     * Tries to remove sensitive data from the payload before it is sent.
     *
     * @param array $serializedPayload The multidimensional array of data to be sent.
     *
     * @return array
     */
    protected function scrub(array &$serializedPayload): array
    {
        $serializedPayload['data'] = $this->config->getScrubber()->scrub($serializedPayload['data']);
        return $serializedPayload;
    }

    /**
     * Reduces the size of the data, so it does not exceed size limits.
     *
     * @param EncodedPayload $payload The payload to possibly truncate.
     *
     * @return EncodedPayload
     */
    protected function truncate(EncodedPayload $payload): EncodedPayload
    {
        return $this->truncation->truncate($payload);
    }

    /**
     * JSON serializes the payload.
     *
     * @param array $payload The data payload to serialize.
     *
     * @return EncodedPayload
     * @throws Exception If JSON serialization fails.
     */
    protected function encode(array $payload): EncodedPayload
    {
        $encoded = new EncodedPayload($payload);
        $encoded->encode();
        return $encoded;
    }
}
