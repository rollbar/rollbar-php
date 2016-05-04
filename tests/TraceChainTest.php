<?php namespace Rollbar\Payload\TraceChain;

class TraceChainTest
{
    public function testTraces()
    {
        $trace = m::mock("Rollbar\Payload\Trace");
        $chain = array($trace);
        $traceChain = new TraceChain($chain);
        $this->assertEquals($chain, $traceChain->getTraces());

        $trace2 = m::mock("Rollbar\Payload\Trace");
        $traceChain = array($trace, $trace2);
        $chain->setTraces($traceChain);
        $this->assertEquals($chain, $traceChain->getTraces());
    }

    public function testKey()
    {
        $chain = new TraceChain();
        $this->assertEquals("trace_chain", $chain->getKey());
    }

    public function testEncode()
    {
        $trace1 = m::mock("Rollabr\Payload\Trace")
            ->shouldReceive("jsonSerialize")
            ->andReturn("TRACE1")
            ->mock();
        $trace2 = m::mock("Rollbar\Payload\Trace")
            ->shouldReceive("jsonSerialize")
            ->andReturn("TRACE2")
            ->mock();
        $chain = new TraceChain(array($trace1, $trace2));
        $this->assertEquals('["TRACE1", "TRACE2"]', json_encode($chain));
    }
}
