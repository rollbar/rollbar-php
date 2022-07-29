<?php namespace Rollbar\Payload\TraceChain;

use Mockery as m;
use Rollbar\Payload\TraceChain;

use Rollbar;
use Rollbar\Payload\Trace;

class TraceChainTest extends Rollbar\BaseRollbarTest
{
    private m\LegacyMockInterface|Trace|m\MockInterface $trace1;
    private m\LegacyMockInterface|Trace|m\MockInterface $trace2;

    public function setUp(): void
    {
        $this->trace1 = m::mock(Trace::class);
        $this->trace2 = m::mock(Trace::class);
    }

    public function testTraces(): void
    {
        $chain = array($this->trace1);
        $traceChain = new TraceChain($chain);
        $this->assertEquals($chain, $traceChain->getTraces());

        $traceChain = new TraceChain($chain);
        $chain = array($this->trace1, $this->trace2);
        $traceChain->setTraces($chain);
        $this->assertEquals($chain, $traceChain->getTraces());
    }

    public function testKey(): void
    {
        $chain = new TraceChain(array($this->trace1));
        $this->assertEquals("trace_chain", $chain->getKey());
    }

    public function testEncode(): void
    {
        $trace1 = m::mock(Trace::class)
            ->shouldReceive("serialize")
            ->andReturn("TRACE1")
            ->mock();
        $trace2 = m::mock(Trace::class)
            ->shouldReceive("serialize")
            ->andReturn("TRACE2")
            ->mock();
        $chain = new TraceChain(array($trace1, $trace2));
        $this->assertEquals('["TRACE1","TRACE2"]', json_encode($chain->serialize()));
    }
}
