<?php declare(strict_types=1);

namespace Rollbar\Telemetry;

use Rollbar\ErrorWrapper;
use Rollbar\Payload\Level;
use Rollbar\Payload\TelemetryBody;
use Rollbar\Payload\TelemetryEvent;
use Stringable;
use Throwable;

/**
 * The Telemeter collects telemetry events and queues them for sending to Rollbar.
 *
 * @since 4.1.0
 */
class Telemeter
{
    public const MAX_EVENTS = 100;

    /**
     * @var TelemetryEvent[] $queue The queue of telemetry events.
     */
    private array $queue = [];

    private int $maxQueueSize;

    /**
     * Initialize a new Telemeter.
     *
     * @param int                           $maxTelemetryEvents      The maximum number of telemetry events to queue
     *                                                               before discarding. Must be between 0 and 100. If,
     *                                                               a value outside the range is given it will be
     *                                                               changed to be within the range. Defaults to 100.
     * @param TelemetryFilterInterface|null $filter                  A filter to apply to telemetry items before they
     *                                                               are added to the queue. If null, no filter will be
     *                                                               applied. Defaults to null.
     * @param bool                          $includeItemsInTelemetry If true, the items caught by Rollbar will be
     *                                                               included in the telemetry of future items sent to
     *                                                               Rollbar.
     * @param bool                          $includeIgnoredItemsInTelemetry
     */
    public function __construct(
        int $maxTelemetryEvents = self::MAX_EVENTS,
        private ?TelemetryFilterInterface $filter = null,
        private bool $includeItemsInTelemetry = true,
        private bool $includeIgnoredItemsInTelemetry = false
    ) {
        $this->maxQueueSize = max(0, min($maxTelemetryEvents, self::MAX_EVENTS));
    }

    /**
     * Returns the Rollbar telemetry type that corresponds to the given PSR-3 log level.
     *
     * @param string $level The PSR-3 log level.
     * @return string
     */
    private static function getTypeFromLevel(string $level): string
    {
        return match ($level) {
            Level::EMERGENCY, Level::ALERT, Level::CRITICAL, Level::ERROR, Level::WARNING => DataType::ERROR,
            Level::NOTICE, Level::INFO => DataType::LOG,
            default => DataType::MANUAL,
        };
    }

    /**
     * Returns the Rollbar telemetry level that corresponds to the given PSR-3 log level.
     *
     * @param string $level The PSR-3 log level.
     * @return string
     */
    private static function getLevelFromLevel(string $level): string
    {
        return match ($level) {
            Level::EMERGENCY, Level::ALERT, Level::CRITICAL => 'critical',
            Level::ERROR => 'error',
            Level::WARNING => 'warning',
            Level::DEBUG => 'debug',
            default => 'info',
        };
    }

    /**
     * Reconfigures the Telemeter with the given options. See the constructor for a description of the options.
     *
     * @param int                           $maxTelemetryEvents
     * @param TelemetryFilterInterface|null $filter
     * @param bool                          $includeItemsInTelemetry
     * @param bool                          $includeIgnoredItemsInTelemetry
     * @return void
     */
    public function scope(
        int $maxTelemetryEvents = self::MAX_EVENTS,
        ?TelemetryFilterInterface $filter = null,
        bool $includeItemsInTelemetry = true,
        bool $includeIgnoredItemsInTelemetry = false
    ): void {
        if ($maxTelemetryEvents !== $this->maxQueueSize) {
            // We call this method so that the queue is truncated if necessary.
            $this->setMaxQueueSize($maxTelemetryEvents);
        }
        if ($filter !== $this->filter) {
            $this->filter = $filter;
        }
        if ($includeItemsInTelemetry !== $this->includeItemsInTelemetry) {
            $this->includeItemsInTelemetry = $includeItemsInTelemetry;
        }
        if ($includeIgnoredItemsInTelemetry !== $this->includeIgnoredItemsInTelemetry) {
            $this->includeIgnoredItemsInTelemetry = $includeIgnoredItemsInTelemetry;
        }
    }

    /**
     * Returns the current queue of telemetry events.
     *
     * Note: this method returns a copy of the queue array, but the TelemetryEvent objects are not cloned, so modifying
     * the events in the returned array will modify the events in the queue.
     *
     * @return TelemetryEvent[]
     */
    public function copyEvents(): array
    {
        if (null === $this->filter || !$this->filter->filterOnRead()) {
            return $this->queue;
        }
        $queue = [];
        $filtered = 0;
        foreach ($this->queue as $event) {
            // The queue size needs to be calculated as the number of events in the queue minus the number of events
            // that have already been filtered.
            if (!$this->filter->include($event, count($this->queue) - $filtered)) {
                $filtered++;
                continue;
            }
            $queue[] = $event;
        }
        return $queue;
    }

    /**
     * Appends a telemetry event to the queue. If the queue is full, the oldest event will be discarded.
     *
     * Note: using this method directly will bypass any filters that have been set on the Telemeter.
     *
     * @param TelemetryEvent $event The telemetry event to add to the queue.
     *
     * @return void
     */
    public function push(TelemetryEvent $event): void
    {
        if ($this->maxQueueSize === 0) {
            return;
        }
        $this->queue[] = $event;
        if (count($this->queue) > $this->maxQueueSize) {
            array_shift($this->queue);
        }
    }

    /**
     * Captures a telemetry event and adds it to the queue.
     *
     * @param string              $type      The type of telemetry data. One of: "log", "network", "dom", "navigation",
     *                                       "error", or "manual".
     * @param string              $level     The severity level of the telemetry data. One of: "critical", "error",
     *                                       "warning", "info", or "debug".
     * @param array|TelemetryBody $metadata  Additional data about the telemetry event.
     * @param string|null         $uuid      The Rollbar UUID to associate with this telemetry event.
     * @param int|null            $timestamp When this occurred, as a unix timestamp in milliseconds. If not provided,
     *                                       the current time will be used.
     *
     * @return TelemetryEvent|null
     */
    public function capture(
        string $type,
        string $level,
        array|TelemetryBody $metadata,
        string $uuid = null,
        ?int $timestamp = null,
    ): ?TelemetryEvent {
        if ($this->maxQueueSize === 0) {
            return null;
        }
        $event = new TelemetryEvent($type, $level, $metadata, $timestamp);
        if (null !== $uuid) {
            $event->uuid = $uuid;
        }
        if (null !== $this->filter && !$this->filter->include($event, count($this->queue))) {
            return null;
        }
        $this->push($event);
        return $event;
    }

    /**
     * Captures an error as a telemetry event and adds it to the queue.
     *
     * @param array|string|ErrorWrapper|Throwable $error     The error to capture. If a string is given, it will be used
     *                                                       as the message. If an array is given, it will be used as
     *                                                       the metadata body. If an ErrorWrapper is given, it will be
     *                                                       parsed for the message and stack trace.
     * @param string                              $level     The severity level of the telemetry data. One of:
     *                                                       "critical", "error", "warning", "info", or "debug".
     *                                                       Defaults to "error".
     * @param string|null                         $uuid      The Rollbar UUID to associate with this telemetry event.
     * @param int|null                            $timestamp When this occurred, as a unix timestamp in milliseconds. If
     *                                                       not provided, the current time will be used.
     *
     * @return TelemetryEvent|null Returns the {@see TelemetryEvent} that was added to the queue, or null if the event
     *                             was filtered out.
     */
    public function captureError(
        array|string|ErrorWrapper|Throwable $error,
        string $level = 'error',
        string $uuid = null,
        ?int $timestamp = null,
    ): ?TelemetryEvent {
        if (is_string($error)) {
            return $this->capture('error', $level, new TelemetryBody(message: $error), $uuid, $timestamp);
        }
        if ($error instanceof ErrorWrapper) {
            $metadata = new TelemetryBody(
                message: $error->getMessage(),
                subtype: 'error',
                stack: $this->stringifyBacktrace($error->getBacktrace()),
            );
            return $this->capture('error', $level, $metadata, $uuid, $timestamp);
        }
        if ($error instanceof Throwable) {
            $metadata = new TelemetryBody(
                message: $error->getMessage(),
                subtype: 'exception',
                stack: $this->stringifyBacktrace($error->getTrace())
            );
            return $this->capture('error', $level, $metadata, $uuid, $timestamp);
        }
        return $this->capture('error', $level, $error, $uuid, $timestamp);
    }

    /**
     * Captures a log message as a telemetry event and adds it to the queue.
     *
     * @param string      $message   The log message to capture.
     * @param string      $level     The severity level of the telemetry data. One of: "critical", "error", "warning",
     *                               "info", or "debug". Defaults to "info".
     * @param string|null $uuid      The Rollbar UUID to associate with this telemetry event.
     * @param int|null    $timestamp When this occurred, as a unix timestamp in milliseconds. If not provided, the
     *                               current time will be used.
     *
     * @return TelemetryEvent|null
     */
    public function captureLog(
        string $message,
        string $level = 'info',
        string $uuid = null,
        ?int $timestamp = null,
    ): ?TelemetryEvent {
        return $this->capture('log', $level, new TelemetryBody(message: $message), $uuid, $timestamp);
    }

    /**
     * Captures a network event as a telemetry event and adds it to the queue.
     *
     * @param string      $method      The HTTP method. E.g. GET, POST, etc.
     * @param string      $url         The URL of the request.
     * @param string      $status_code The HTTP status code.
     * @param string      $level       The severity level of the telemetry data. One of: "critical", "error", "warning",
     *                                 "info", or "debug". Defaults to "info".
     * @param string|null $uuid        The Rollbar UUID to associate with this telemetry event.
     * @param int|null    $timestamp   When this occurred, as a unix timestamp in milliseconds. If not provided, the
     *                                 current time will be used.
     *
     * @return TelemetryEvent|null
     */
    public function captureNetwork(
        string $method,
        string $url,
        string $status_code,
        string $level = 'info',
        string $uuid = null,
        ?int $timestamp = null,
    ): ?TelemetryEvent {
        return $this->capture(
            type: 'log',
            level: $level,
            metadata: new TelemetryBody(
                method: $method,
                url: $url,
                status_code: $status_code,
            ),
            uuid: $uuid,
            timestamp: $timestamp,
        );
    }

    /**
     * Captures a navigation event as a telemetry event and adds it to the queue.
     *
     * @param string      $from      The URL of the previous page.
     * @param string      $to        The URL of the next page.
     * @param string      $level     The severity level of the telemetry data. One of: "critical", "error", "warning",
     *                               "info", or "debug". Defaults to "info".
     * @param string|null $uuid      The Rollbar UUID to associate with this telemetry event.
     * @param int|null    $timestamp When this occurred, as a unix timestamp in milliseconds. If not provided, the
     *                               current time will be used.
     *
     * @return TelemetryEvent|null
     */
    public function captureNavigation(
        string $from,
        string $to,
        string $level = 'info',
        string $uuid = null,
        ?int $timestamp = null,
    ): ?TelemetryEvent {
        return $this->capture('log', $level, new TelemetryBody(from: $from, to: $to), $uuid, $timestamp);
    }

    /**
     * Add a Rollbar captured item to the telemetry queue.
     *
     * @param string                      $level   The PSR-3 log level.
     * @param string|Stringable|Throwable $message The message to log.
     * @param array                       $context The context.
     * @param bool                        $ignored Whether the item was ignored.
     * @param string|null                 $uuid    The Rollbar item UUID.
     *
     * @return TelemetryEvent|null
     *
     * @internal This method is for internal use only and may change without warning.
     */
    public function captureRollbarItem(
        string $level,
        string|Stringable|Throwable $message,
        array $context = [],
        bool $ignored = false,
        ?string $uuid = null,
    ): ?TelemetryEvent {
        if (!$this->includeItemsInTelemetry) {
            return null;
        }
        if (!$this->includeIgnoredItemsInTelemetry && $ignored) {
            return null;
        }
        if (null !== $this->filter && !$this->filter->includeRollbarItem($level, $message, $context, $ignored)) {
            return null;
        }
        // Make sure to respect the PSR exception context. See https://www.php-fig.org/psr/psr-3/#13-context.
        if (($context['exception'] ?? null) instanceof Throwable) {
            $event = $this->captureError($context['exception'], self::getLevelFromLevel($level), $uuid);
            if (null === $event) {
                return null;
            }
            // We have both a message from the exception instance and a message. So we will use the message as the
            // primary body message, and the exception message will be saved to a custom "error_message" property on
            // the posted telemetry event body.
            $event->body->extra['error_message'] = $event->body->message;
            $event->body->message = $this->getRollbarItemMessage($message);
            return $event;
        }
        // If the rollbar item is an exception, we should capture it as an error event.
        if ($message instanceof Throwable) {
            return $this->captureError($message, self::getLevelFromLevel($level), $uuid);
        }
        // Otherwise, we will capture it based on the level.
        return $this->capture(
            type: self::getTypeFromLevel($level),
            level: self::getLevelFromLevel($level),
            metadata: new TelemetryBody(message: $this->getRollbarItemMessage($message)),
            uuid: $uuid,
        );
    }

    /**
     * Returns the maximum number of telemetry events that can be queued before discarding prior events.
     *
     * @return int
     */
    public function getMaxQueueSize(): int
    {
        return $this->maxQueueSize;
    }

    /**
     * Update the maximum number of telemetry events that can be queued before discarding. NOTE: If the new max is less
     * than the current number of queued events, the oldest events will be discarded.
     *
     * @param int $maxQueueSize The maximum number of telemetry events to queue before discarding. Must be between 0
     *                          and 100.
     */
    public function setMaxQueueSize(int $maxQueueSize): void
    {
        $newMax = max(0, min($maxQueueSize, self::MAX_EVENTS));
        $queueSize = count($this->queue);
        if ($queueSize > $newMax) {
            array_splice($this->queue, 0, $queueSize - $newMax);
        }
        $this->maxQueueSize = $newMax;
    }

    /**
     * Returns the current number of telemetry events in the queue.
     *
     * @return int
     */
    public function getQueueSize(): int
    {
        return count($this->queue);
    }

    /**
     * Clears the queue of all telemetry events.
     *
     * @return void
     */
    public function clearQueue(): void
    {
        $this->queue = [];
    }

    /**
     * If true, the items caught by Rollbar will be included in the telemetry of future items sent to Rollbar.
     *
     * @return bool
     */
    public function shouldIncludeItemsInTelemetry(): bool
    {
        return $this->includeItemsInTelemetry;
    }

    /**
     * Change whether Rollbar items should be included in the telemetry queue.
     *
     * @param bool $include True to include Rollbar items in the telemetry data.
     */
    public function setIncludeItemsInTelemetry(bool $include): void
    {
        $this->includeItemsInTelemetry = $include;
    }

    /**
     * Returns the filter instance that is applied to telemetry items before they are added to the queue.
     *
     * @return TelemetryFilterInterface|null
     */
    public function getFilter(): ?TelemetryFilterInterface
    {
        return $this->filter;
    }

    /**
     * Sets the filter to apply to telemetry items before they are added to the queue. This will also apply the new
     * filter to any items already in the queue if $apply is true.
     *
     * @param TelemetryFilterInterface|null $filter A filter to apply to telemetry items before they are added to the
     *                                              queue. If null, no filter will be applied.
     * @param bool                          $apply  If true, the new filter will be applied to any items already in
     *                                              the queue.
     */
    public function setFilter(?TelemetryFilterInterface $filter, bool $apply = true): void
    {
        $this->filter = $filter;
        if (null === $filter || !$apply) {
            return;
        }
        $tempQueue = [];
        $filtered = 0;
        foreach ($this->queue as $event) {
            // The queue size needs to be calculated as the number of events in the queue minus the number of events
            // that have already been filtered.
            if (!$this->filter->include($event, count($this->queue) - $filtered)) {
                $filtered++;
                continue;
            }
            $tempQueue[] = $event;
        }
        $this->queue = $tempQueue;
    }

    /**
     * Returns true if a Rollbar captured item that has been ignored should still be included in the telemetry data.
     *
     * @return bool
     */
    public function shouldIncludeIgnoredItemsInTelemetry(): bool
    {
        return $this->includeIgnoredItemsInTelemetry;
    }

    /**
     * Sets whether items captured by Rollbar should be included in the telemetry data even if they are ignored.
     *
     * @param bool $include True to include ignored items in the telemetry data.
     * @return void
     */
    public function setIncludeIgnoredItemsInTelemetry(bool $include): void
    {
        $this->includeIgnoredItemsInTelemetry = $include;
    }

    /**
     * Returns the message from a Rollbar reported item.
     *
     * @param string|Stringable|Throwable $message The message to log.
     *
     * @return string
     */
    private function getRollbarItemMessage(string|Stringable|Throwable $message): string
    {
        if (is_string($message)) {
            return $message;
        }
        if ($message instanceof Throwable) {
            return $message->getMessage();
        }
        // else $message is a Stringable instance
        return $message->__toString();
    }

    /**
     * Given a standard PHP backtrace array, returns a string representation of the backtrace.
     *
     * @param array $backtrace The backtrace array.
     * @return string
     */
    private function stringifyBacktrace(array $backtrace): string
    {
        $result = '';
        foreach ($backtrace as $i => $frame) {
            $result .= '#' . $i . ' ';
            $result .= $frame['class'] ?? '';
            $result .= $frame['type'] ?? '';
            $result .= $frame['function'] ?? '';
            if (isset($frame['args'])) {
                $result .= '(';
                $result .= implode(', ', array_map(fn($arg) => is_string($arg) ? $arg : gettype($arg), $frame['args']));
                $result .= ')';
            }
            $result .= ' at ';
            $result .= $frame['file'] ?? '';
            $result .= ':';
            $result .= $frame['line'] ?? '';
            $result .= "\n";
        }
        return $result;
    }
}
