<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\Telemetry\DataType;
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
     * - If $type is {@see DataType::LOG}, the body should contain "message" key.
     * - If $type is {@see DataType::NETWORK}, the body should contain "method", "url", and "status_code" keys.
     * - If $type is {@see DataType::NAVIGATION}, the body should contain "from" and "to" keys.
     * - If $type is {@see DataType::ERROR}, the body should contain "message" key.
     *
     * @param string              $type      The type of telemetry data. One of: {@see DataType}.
     * @param string              $level     The severity level of the telemetry data. One of: "critical", "error",
     *                                       "warning", "info", or "debug".
     * @param array|TelemetryBody $body      Additional data for the telemetry event. If an array is provided, it will
     *                                       be converted to a {@see TelemetryBody} object.
     * @param float|null          $timestamp When this occurred, as a unix timestamp in milliseconds. If not provided,
     *                                       Rollbar will use the current time.
     */
    public function __construct(
        public string $type,
        public string $level,
        array|TelemetryBody $body,
        public ?float $timestamp = null,
    ) {
        if (is_null($this->timestamp)) {
            $this->timestamp = floor(microtime(true) * 1000);
        }
        $this->body = is_array($body) ? new TelemetryBody(...$body): $body;
    }

    public function serialize(): array
    {
        $result = array_filter([
            'uuid' => $this->uuid,
            'source' => $this->source,
            'level' => $this->level,
            'type' => $this->type,
            'body' => $this->body->serialize(),
            'timestamp_ms' => $this->timestamp,
        ]);

        return $this->utilities()->serializeForRollbarInternal($result);
    }
}
