<?php namespace Rollbar;

use Rollbar\ErrorWrapper;

class ErrorWrapperTest
{
    public function testTrace()
    {
        $errWrapper = new ErrorWrapper(null, null, null, null, "FAKE BACKTRACE");
        $this->assertEquals("FAKE BACKTRACE", $errWrapper->getTrace());
    }

    public function testGetClassName()
    {
        $errWrapper = new ErrorWrapper(E_ERROR, "Message Content", null, null, null);
        $this->assertEquals("E_ERROR: Message Content", $errWrapper->getClassName());

        $errWrapper = new ErrorWrapper(3, "Fake Error Number", null, null, null);
        $this->assertEqusl("#3: Fake Error Number", $errWrapper->getClassName());
    }
}
