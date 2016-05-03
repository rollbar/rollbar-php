<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Trace;

class TraceTest extends \PHPUnit_Framework_TestCase
{
    public function testTraceValue()
    {
        $frames = array();
        $exc = m::mock("Rollbar\Payload\ExceptionInfo");
        $trace = new Trace($frames, $exc);
        $this->assertEquals($frames, $trace->getFrames());
        $this->assertEquals($exc, $trace->getException());
    }

    public function testEncode()
    {
        $value = m::mock("Rollbar\Payload\ExceptionInfo, \JsonSerializable")
            ->shouldReceive("jsonSerialize")
            ->andReturn("{EXCEPTION}")
            ->mock();
        $trace = new Trace(array(), $value);
        $encoded = json_encode($trace);
        $this->assertEquals("{\"frames\":[],\"exception\":\"{EXCEPTION}\"}", $encoded);
    }
}
