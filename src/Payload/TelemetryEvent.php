<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\Telemetry\EventLevel;
use Rollbar\Telemetry\EventType;
use Rollbar\UtilitiesTrait;

/**
 * A telemetry event.
 *
 * @since 4.1.0
 */
class TelemetryEvent implements SerializerInterface
{
    use UtilitiesTrait;

    public ?string $uuid = null;
    public ?string $source = 'server';

    public TelemetryBody $body;

    /**
     * Creates a telemetry event.
     *
     * Some types should be accompanied by specific data in the body.
     *
     * - If $type is {@see EventType::Log}, the body should contain "message" key.
     * - If $type is {@see EventType::Network}, the body should contain "method", "url", and "status_code" keys.
     * - If $type is {@see EventType::Navigation}, the body should contain "from" and "to" keys.
     * - If $type is {@see EventType::Error}, the body should contain "message" key.
     *
     * @param EventType           $type      The type of telemetry data.
     * @param EventLevel          $level     The severity level of the telemetry data.
     * @param array|TelemetryBody $body      Additional data for the telemetry event. If an array is provided, it will
     *                                       be converted to a {@see TelemetryBody} object.
     * @param float|null          $timestamp When this occurred, as a unix timestamp in milliseconds. If not provided,
     *                                       Rollbar will use the current time.
     */
    public function __construct(
        public EventType $type,
        public EventLevel $level,
        array|TelemetryBody $body,
        public ?float $timestamp = null,
    ) {
        if (is_null($this->timestamp)) {
            $this->timestamp = floor(microtime(true) * 1000);
        }
        $this->body = is_array($body) ? TelemetryBody::fromArray($body): $body;
    }

    public function serialize(): array
    {
        $result = array_filter([
            'uuid' => $this->uuid,
            'source' => $this->source,
            'level' => $this->level->value,
            'type' => $this->type->value,
            'body' => $this->body->serialize(),
            'timestamp_ms' => $this->timestamp,
        ]);

        return $this->utilities()->serializeForRollbarInternal($result);
    }
}
