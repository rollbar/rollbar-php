<?php namespace Rollbar\Payload;

use Rollbar;

class ExceptionInfoTest extends Rollbar\BaseRollbarTest
{
    public function testClass()
    {
        $class = "HelloWorld";
        $exc = new ExceptionInfo($class, "message");
        $this->assertEquals($class, $exc->getClass());

        $this->assertEquals("TestClass", $exc->setClass("TestClass")->getClass());
    }

    public function testMessage()
    {
        $message = "A message";
        $exc = new ExceptionInfo("C", $message);
        $this->assertEquals($message, $exc->getMessage());

        $this->assertEquals("Another", $exc->setMessage("Another")->getMessage());
    }

    public function testDescription()
    {
        $description = "long form";
        $exc = new ExceptionInfo("C", "s", $description);
        $this->assertEquals($description, $exc->getDescription());

        $this->assertEquals("longer form", $exc->setDescription("longer form")->getDescription());
        $this->assertNull($exc->setDescription(null)->getDescription());
    }
}
