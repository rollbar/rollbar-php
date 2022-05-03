<?php namespace Rollbar;

use Mockery as m;
use Rollbar\Payload\Trace;
use Rollbar\Payload\Frame;
use Rollbar\Payload\ExceptionInfo;

class TraceTest extends BaseRollbarTest
{
    public function testTraceConstructor(): void
    {
        $exc = m::mock(ExceptionInfo::class);
        $frames = array(m::mock(Frame::class));
        $badFrames = array(1);

        $trace = new Trace(array(), $exc);
        $this->assertEquals(array(), $trace->getFrames());
        $this->assertEquals($exc, $trace->getException());

        $trace = new Trace($frames, $exc);
        $this->assertEquals($frames, $trace->getFrames());
        $this->assertEquals($exc, $trace->getException());
    }

    public function testFrames(): void
    {
        $frames = array(
            new Frame("one.php"),
            new Frame("two.php")
        );
        $exc = m::mock(ExceptionInfo::class);
        $trace = new Trace(array(), $exc);
        $this->assertEquals($frames, $trace->setFrames($frames)->getFrames());
    }

    public function testException(): void
    {
        $exc = m::mock(ExceptionInfo::class);
        $trace = new Trace(array(), $exc);
        $this->assertEquals($exc, $trace->getException());

        $exc2 = m::mock(ExceptionInfo::class);
        $this->assertEquals($exc2, $trace->setException($exc2)->getException());
    }

    public function testEncode(): void
    {
        $value = m::mock("Rollbar\Payload\ExceptionInfo, Rollbar\SerializerInterface")
            ->shouldReceive("serialize")
            ->andReturn("{EXCEPTION}")
            ->mock();
        $trace = new Trace(array(), $value);
        $encoded = json_encode($trace->serialize());
        $this->assertEquals("{\"frames\":[],\"exception\":\"{EXCEPTION}\"}", $encoded);
    }

    public function testTraceKey(): void
    {
        $trace = new Trace(array(), m::mock(ExceptionInfo::class));
        $this->assertEquals("trace", $trace->getKey());
    }
}
