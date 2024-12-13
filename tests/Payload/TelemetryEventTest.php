<?php

namespace Payload;

use Rollbar\BaseRollbarTest;
use Rollbar\Payload\TelemetryEvent;
use Rollbar\Telemetry\EventLevel;
use Rollbar\Telemetry\EventType;

class TelemetryEventTest extends BaseRollbarTest
{
    public function testConstruct(): void
    {
        // Make sure the timestamp is automatically created if it is null.
        $before = floor(microtime(true) * 1000);
        $event = new TelemetryEvent(EventType::Log, EventLevel::Info, ['message' => 'foo']);
        $after = floor(microtime(true) * 1000);

        self::assertNotNull($event->timestamp);
        self::assertGreaterThanOrEqual($before, $event->timestamp);
        self::assertLessThanOrEqual($after, $event->timestamp);
    }
}
