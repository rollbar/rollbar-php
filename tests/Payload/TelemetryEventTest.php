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

    /**
     * @since 4.1.1
     */
    public function testNestedArrayBody(): void
    {
        $event = new TelemetryEvent(EventType::Network, EventLevel::Info, [
            'method' => 'GET',
            'url' => 'https://example.com',
            'status_code' => '200',
            [
                'unstructured' => 'data',
                0 => 'foo',
            ],
        ]);

        self::assertSame('GET', $event->body->method);
        self::assertSame('https://example.com', $event->body->url);
        self::assertSame('200', $event->body->status_code);
        self::assertSame([
            [
                'unstructured' => 'data',
                0 => 'foo',
            ],
        ], $event->body->extra);
    }
}
