<?php

namespace Payload;

use Rollbar\BaseRollbarTest;
use Rollbar\Payload\TelemetryBody;

class TelemetryBodyTest extends BaseRollbarTest
{
    public function testConstruct(): void
    {
        $body = new TelemetryBody(
            message: 'message',
            method: 'method',
            url: 'url',
            status_code: 'status',
            subtype: 'sub',
            stack: 'stack',
            from: 'from',
            to: 'to',
            start_timestamp_ms: 42,
            end_timestamp_ms: 43,
            extraOne: 'foo',
            extraTwo: 'bar',
        );

        self::assertSame('message', $body->message);
        self::assertSame('method', $body->method);
        self::assertSame('url', $body->url);
        self::assertSame('status', $body->status_code);
        self::assertSame('sub', $body->subtype);
        self::assertSame('stack', $body->stack);
        self::assertSame('from', $body->from);
        self::assertSame('to', $body->to);
        self::assertSame(42, $body->start_timestamp_ms);
        self::assertSame(43, $body->end_timestamp_ms);
        self::assertSame([
            'extraOne' => 'foo',
            'extraTwo' => 'bar',
        ], $body->extra);

        // Assert array order does not matter.
        $body = new TelemetryBody(...[
            'message' => 'message',
            'extraOne' => 'foo',
            'stack' => 'stack',
        ]);

        self::assertSame('message', $body->message);
        self::assertSame('foo', $body->extra['extraOne']);
        self::assertSame('stack', $body->stack);
    }

    public function testSerialize(): void
    {
        $body = new TelemetryBody(
            message: 'message',
            method: 'method',
            url: 'url',
            status_code: 'status',
            subtype: 'sub',
            stack: 'stack',
            from: 'from',
            to: 'to',
            start_timestamp_ms: 42,
            end_timestamp_ms: 43,
            extraOne: 'foo',
            extraTwo: 'bar',
        );

        self::assertSame([
            'message' => 'message',
            'method' => 'method',
            'url' => 'url',
            'status_code' => 'status',
            'subtype' => 'sub',
            'stack' => 'stack',
            'from' => 'from',
            'to' => 'to',
            'start_timestamp_ms' => 42,
            'end_timestamp_ms' => 43,
            'extraOne' => 'foo',
            'extraTwo' => 'bar',
        ], $body->serialize());
    }

    public function testEmptyProperties(): void
    {
        $body = new TelemetryBody();
        self::assertEmpty($body->serialize());
    }

    public function testExtraDoesNotOverrideProperty(): void
    {
        $body = new TelemetryBody(message: 'foo');
        $body->extra['message'] = 'bar';

        self::assertSame(['message' => 'foo'], $body->serialize());
    }

    /**
     * @since 4.1.1
     */
    public function testFromArray(): void
    {
        $body = TelemetryBody::fromArray([
            'message' => 'message',
            'method' => 'method',
            'url' => 'url',
            'status_code' => 'status',
            'subtype' => 'sub',
            'stack' => 'stack',
            'from' => 'from',
            'to' => 'to',
            'start_timestamp_ms' => 42,
            'end_timestamp_ms' => 43,
            'extraOne' => 'foo',
            'extraTwo' => 'bar',
        ]);

        self::assertSame('message', $body->message);
        self::assertSame('method', $body->method);
        self::assertSame('url', $body->url);
        self::assertSame('status', $body->status_code);
        self::assertSame('sub', $body->subtype);
        self::assertSame('stack', $body->stack);
        self::assertSame('from', $body->from);
        self::assertSame('to', $body->to);
        self::assertSame(42, $body->start_timestamp_ms);
        self::assertSame(43, $body->end_timestamp_ms);
        self::assertSame([
            'extraOne' => 'foo',
            'extraTwo' => 'bar',
        ], $body->extra);
    }

    /**
     * @since 4.1.1
     */
    public function testFromArrayNested(): void
    {
        // Assert that nested/non-standard arrays don't throw an error.
        $body = TelemetryBody::fromArray([["data" => "some data"]]);
        self::assertSame([["data" => "some data"]], $body->extra);

        // Assert that positional arguments are not passed to named arguments on the constructor.
        $body = TelemetryBody::fromArray(["0" => ["id" => "some id"], 'message' => 'test message']);
        self::assertSame('test message', $body->message);
        self::assertSame(["0" => ["id" => "some id"]], $body->extra);
    }
}
