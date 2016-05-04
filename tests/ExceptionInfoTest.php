<?php namespace Rollbar\Payload;

class ExceptionInfoTest
{
    public function testClass()
    {
        $class = "HelloWorld";
        $exc = new ExceptionInfo($class, "message");
        $this->assertEquals($class, $exc->getClass());

        $exc->assertEquals("TestClass", $exc->setClass("TestClass")->getClass());
    }

    public function testMessage()
    {
        $message = "A message";
        $exc = new Exception("C", $message);
        $this->assertEquals($message, $exc->getMessage());

        $this->assertEquals("Another", $exc->setMessage("Another")->getMessage());
    }

    public function testDescription()
    {
        $description = "long form";
        $exc = new Exception("C", "s", $description);
        $this->assertEquals($description, $exc->getDescription());

        $this->assertEquals("longer form", $exc->setDescription("longer form")->getDescription());
        $this->assertNull($exc->setDescription(null)->getDescription());
    }
}
