<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

/**
 * The body of a telemetry event.
 *
 * @since 4.1.0
 */
class TelemetryBody implements SerializerInterface
{
    use UtilitiesTrait;

    public const DEFINED_KEYS = [
        'message',
        'method',
        'url',
        'status_code',
        'subtype',
        'stack',
        'element',
        'from',
        'to',
        'start_timestamp_ms',
        'end_timestamp_ms',
    ];

    /**
     * @var array $extra Any extra data to include in the telemetry body.
     */
    public array $extra = [];

    /**
     * Creates the telemetry body.
     *
     * The `element` property is not included in the constructor because it intended for browser DOM events.
     *
     * @param string      $message            This should be included for errors and log events.
     * @param string      $method             This should be included for network events. The HTTP method. E.g. GET,
     *                                        POST, etc.
     * @param string      $url                This should be included for network events. The URL of the request.
     * @param string      $status_code        This should be included for network events. The HTTP status code.
     * @param string      $subtype            This can be used to further classify the event. Internally, we use this to
     *                                        distinguish between errors and exceptions for error events.
     * @param string|null $stack              The stack trace of the error or exception. This should be included for
     *                                        error events.
     * @param string      $from               This should be included for navigation events. The URL of the previous
     *                                        page.
     * @param string      $to                 This should be included for navigation events. The URL of the next page.
     * @param int|null    $start_timestamp_ms The start time of the event in milliseconds since the Unix epoch.
     * @param int|null    $end_timestamp_ms   The end time of the event in milliseconds since the Unix epoch.
     * @param mixed       ...$extra           Any extra data to include in the telemetry body.
     */
    public function __construct(
        public string $message = '',
        public string $method = '',
        public string $url = '',
        public string $status_code = '',
        public string $subtype = '',
        public ?string $stack = null,
        public string $from = '',
        public string $to = '',
        public ?int $start_timestamp_ms = null,
        public ?int $end_timestamp_ms = null,
        mixed ...$extra,
    ) {
        $this->extra = $extra;
    }

    /**
     * Returns the array representation of the telemetry body.
     *
     * @return array
     */
    public function serialize(): array
    {
        // This filters out any null or empty values.
        $result = array_filter([
            'message' => $this->message,
            'method' => $this->method,
            'url' => $this->url,
            'status_code' => $this->status_code,
            'subtype' => $this->subtype,
            'stack' => $this->stack,
            'from' => $this->from,
            'to' => $this->to,
            'start_timestamp_ms' => $this->start_timestamp_ms,
            'end_timestamp_ms' => $this->end_timestamp_ms,
        ]);

        if (empty($this->extra)) {
            return $result;
        }

        // This keeps the extra data from overwriting the defined keys when the extra data is merged into the result.
        $extra = array_diff_key($this->extra, array_fill_keys(self::DEFINED_KEYS, null));

        return $this->utilities()->serializeForRollbarInternal(array_merge($result, $extra));
    }
}
