<?php namespace Rollbar\Payload\TraceChain;

use \Mockery as m;
use Rollbar\Payload\TraceChain;

use Rollbar;

class TraceChainTest extends Rollbar\BaseRollbarTest
{
    private $trace1;
    private $trace2;

    public function setUp()
    {
        $this->trace1 = m::mock("Rollbar\Payload\Trace");
        $this->trace2 = m::mock("Rollbar\Payload\Trace");
    }

    public function testTraces()
    {
        $chain = array($this->trace1);
        $traceChain = new TraceChain($chain);
        $this->assertEquals($chain, $traceChain->getTraces());

        $traceChain = new TraceChain($chain);
        $chain = array($this->trace1, $this->trace2);
        $traceChain->setTraces($chain);
        $this->assertEquals($chain, $traceChain->getTraces());
    }

    public function testKey()
    {
        $chain = new TraceChain(array($this->trace1));
        $this->assertEquals("trace_chain", $chain->getKey());
    }

    public function testEncode()
    {
        $trace1 = m::mock("Rollbar\Payload\Trace")
            ->shouldReceive("serialize")
            ->andReturn("TRACE1")
            ->mock();
        $trace2 = m::mock("Rollbar\Payload\Trace")
            ->shouldReceive("serialize")
            ->andReturn("TRACE2")
            ->mock();
        $chain = new TraceChain(array($trace1, $trace2));
        $this->assertEquals('["TRACE1","TRACE2"]', json_encode($chain->serialize()));
    }
}
