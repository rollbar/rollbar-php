<?php namespace Rollbar;

use Mockery as m;
use Rollbar\Payload\Frame;
use Rollbar\Payload\Context;
use Rollbar\Payload\ExceptionInfo;

class FrameTest extends BaseRollbarTest
{
    private m\LegacyMockInterface|m\MockInterface|ExceptionInfo $exception;
    private Frame $frame;

    public function setUp(): void
    {
        $this->exception = m::mock(ExceptionInfo::class);
        $this->frame = new Frame("tests/FrameTest.php");
    }

    public function testFilename(): void
    {
        $frame = new Frame("filename.php");
        $this->assertEquals("filename.php", $frame->getFilename());
        $frame->setFilename("other.php");
        $this->assertEquals("other.php", $frame->getFilename());
    }

    public function testLineno(): void
    {
        $this->frame->setLineno(5);
        $this->assertEquals(5, $this->frame->getLineno());
    }

    public function testColno(): void
    {
        $this->frame->setColno(5);
        $this->assertEquals(5, $this->frame->getColno());
    }

    public function testMethod(): void
    {
        $this->frame->setMethod("method");
        $this->assertEquals("method", $this->frame->getMethod());
    }

    public function testCode(): void
    {
        $this->frame->setCode("code->whatever()");
        $this->assertEquals("code->whatever()", $this->frame->getCode());
    }

    public function testContext(): void
    {
        $context = m::mock(Context::class);
        $this->frame->setContext($context);
        $this->assertEquals($context, $this->frame->getContext());
    }

    public function testArgs(): void
    {
        $this->frame->setArgs(array());
        $this->assertEquals(array(), $this->frame->getArgs());

        $this->frame->setArgs(array(1, "hi"));
        $this->assertEquals(array(1, "hi"), $this->frame->getArgs());
    }

    public function testEncode(): void
    {
        $context = m::mock("Rollbar\Payload\Context, Rollbar\SerializerInterface")
            ->shouldReceive("serialize")
            ->andReturn("{CONTEXT}")
            ->mock();
        $this->exception
            ->shouldReceive("serialize")
            ->andReturn("{EXC}")
            ->mock();
        $this->frame->setFilename("rollbar.php")
            ->setLineno(1024)
            ->setColno(42)
            ->setMethod("testEncode()")
            ->setCode('$frame->setFilename("rollbar.php")')
            ->setContext($context)
            ->setArgs(array("hello", "world"));

        $actual = json_encode($this->frame->serialize());
        $expected = '{' .
                '"filename":"rollbar.php",' .
                '"lineno":1024,"colno":42,' .
                '"method":"testEncode()",' .
                '"code":"$frame->setFilename(\"rollbar.php\")",' .
                '"context":"{CONTEXT}",' .
                '"args":["hello","world"]' .
            '}';

        $this->assertEquals($expected, $actual);
    }
}
