<?php declare(strict_types=1);

namespace Rollbar\Telemetry;

use Rollbar\Payload\TelemetryEvent;
use Stringable;

/**
 * The TelemetryFilterInterface allows you to filter telemetry events from the reporting events sent to Rollbar.
 *
 * An optional constructor can be included in the implementing class. The constructor should accept a single array
 * argument. The value of the argument will be the value of `telemetry` from the configs array, or an empty array if
 * the `telemetry` key does not exist.
 *
 * A new instance of the filter will be created any time the configuration is changed.
 *
 * @since 4.1.0
 */
interface TelemetryFilterInterface
{
    /**
     * Filter the given telemetry event.
     *
     * This method may be called multiple times for the same event, so it should be idempotent. It also may be called
     * before an event is added to the queue, and then again before the event is sent to Rollbar.
     *
     * @param TelemetryEvent $event     The telemetry event to include or exclude from the telemetry data.
     * @param int            $queueSize The current size of the telemetry queue.
     *
     * @return bool True if the event should be included in the telemetry queue, false if it should be excluded.
     */
    public function include(TelemetryEvent $event, int $queueSize): bool;

    /**
     * Filter if a Rollbar captured or reported item should be included in the telemetry data.
     *
     * This method will be called prior to the {@see include()} method for captured or reported items. If this method
     * returns `false`, then the item will not be included in the telemetry data. However, it may still be sent to
     * Rollbar as the main reported item. To prevent the item from being sent to Rollbar at all, you should also
     * implement the {@see \Rollbar\FilterInterface} and return `false` from the
     * {@see \Rollbar\FilterInterface::shouldSend()} method.
     *
     * This method is called before {@see \Rollbar\FilterInterface::shouldSend()} and so the `$ignored` parameter will
     * only reflect the internal filtering based on the minimum log level and PHP error reporting level.
     *
     * This method will not be called in two scenarios:
     *
     * 1. Rollbar items in telemetry data are disabled by setting the `telemetry => includeItemsInTelemetry` config
     *    option to `false`.
     * 2. Ignored Rollbar items are not included in the telemetry data by changing the default value of the
     *    `telemetry => includeIgnoredItemsInTelemetry` config option to `true`. And the reported item is ignored
     *    because of its log level or PHP error reporting level.
     *
     * @param string            $level   The PSR-3 log level.
     * @param string|Stringable $message The message to log.
     * @param array             $context The context.
     * @param bool              $ignored Whether the item was ignored as a result of the configuration. If false, then
     *                                   the item will not be sent to Rollbar. However, you may still want to include
     *                                   it in the telemetry data.
     *
     * @return bool True if the item should be included in the telemetry queue, false if it should be excluded.
     */
    public function includeRollbarItem(
        string $level,
        string|Stringable $message,
        array $context = [],
        bool $ignored = false,
    ): bool;

    /**
     * Returns `true` if the {@see include()} method should be called not only before the event is added to the queue,
     * but also before the event is sent to Rollbar. This means the {@see include()} method will be called twice for
     * each event.
     *
     * If this method returns `false`, then the {@see include()} method will only be called before the event is added to
     * the queue.
     *
     * @return bool
     */
    public function filterOnRead(): bool;
}
