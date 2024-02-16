<?php

namespace Rollbar\Truncation;

use Exception;
use Rollbar\Payload\EncodedPayload;
use Rollbar\Rollbar;

/**
 * Truncation strategy for telemetry in payloads.
 *
 * @since 4.1.0
 */
class TelemetryStrategy extends AbstractStrategy
{
    /**
     * The number of telemetry events to keep at the beginning and end of the telemetry array.
     */
    const TELEMETRY_OPTIMIZATION_RANGE = 5;

    /**
     * Removes telemetry events from the middle of the telemetry array. Will keep the number of telemetry events
     * specified by $range at the start and end of the array.
     *
     * @param array $telemetry The list of telemetry events.
     * @param int   $range     The number of telemetry events to keep on each end of the telemetry array.
     * @return array
     *
     * @since 4.1.0
     */
    public static function selectTelemetry(array $telemetry, int $range = self::TELEMETRY_OPTIMIZATION_RANGE): array
    {
        if (count($telemetry) <= $range * 2) {
            return $telemetry;
        }

        return array_merge(
            array_slice($telemetry, 0, $range),
            array_slice($telemetry, -$range)
        );
    }

    /**
     * Truncates the data in the payload by removing excess telemetry events from the middle of the telemetry array.
     *
     * @param EncodedPayload $payload The payload to truncate.
     * @return EncodedPayload
     *
     * @throws Exception If the payload encoding fails.
     *
     * @since 4.1.0
     */
    public function execute(EncodedPayload $payload): EncodedPayload
    {
        $data = $payload->data();

        // If telemetry is not enabled, then remove the telemetry data from the payload entirely.
        if (null === Rollbar::getTelemeter()) {
            unset($data['data']['body']['telemetry']);
            $payload->encode($data);
            return $payload;
        }

        if (!isset($data['data']['body']['telemetry'])) {
            return $payload;
        }

        $data['data']['body']['telemetry'] = self::selectTelemetry($data['data']['body']['telemetry']);
        $payload->encode($data);

        return $payload;
    }

    /**
     * Returns true if the given payload contains telemetry data. This is irrespective of whether the telemetry is
     * enabled or not.
     *
     * @param EncodedPayload $payload The payload to truncate.
     *
     * @return bool
     *
     * @since 4.1.0
     */
    public function applies(EncodedPayload $payload): bool
    {
        // If the payload does not telemetry data, then this strategy does not apply.
        return isset($payload->data()['data']['body']['telemetry']);
    }
}
