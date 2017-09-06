<?php namespace Rollbar;

class UtilitiesTest extends BaseRollbarTest
{
    public function testValidateString()
    {
        Utilities::validateString("");
        Utilities::validateString("true");
        Utilities::validateString("four", "local", 4);
        Utilities::validateString(null);

        try {
            Utilities::validateString(null, "null", null, false);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$null must not be null");
        }

        try {
            Utilities::validateString(1, "number");
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$number must be a string");
        }

        try {
            Utilities::validateString("1", "str", 2);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$str must be 2 characters long, was '1'");
        }
    }

    public function testValidateInteger()
    {
        Utilities::validateInteger(null);
        Utilities::validateInteger(0);
        Utilities::validateInteger(1, "one", 0, 2);

        try {
            Utilities::validateInteger(null, "null", null, null, false);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$null must not be null");
        }

        try {
            Utilities::validateInteger(0, "zero", 1);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$zero must be >= 1");
        }

        try {
            Utilities::validateInteger(0, "zero", null, -1);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$zero must be <= -1");
        }
    }

    public function testValidateBooleanThrowsException()
    {
        $this->setExpectedException(get_class(new \InvalidArgumentException()));
        Utilities::validateBoolean(null, "foo", false);
    }

    public function testValidateBooleanWithInvalidBoolean()
    {
        $this->setExpectedException(get_class(new \InvalidArgumentException()));
        Utilities::validateBoolean("not a boolean");
    }

    public function testValidateBoolean()
    {
        Utilities::validateBoolean(true, "foo", false);
        Utilities::validateBoolean(true);
        Utilities::validateBoolean(null);
    }

    public function testSerializeForRollbar()
    {
        $obj = array(
            "one_two" => array(1, 2),
            "class" => "Numbers",
            "php_unit_test" => "testSerializeForRollbar",
            "myCustomKey" => null,
            "myNullValue" => null,
        );
        $result = Utilities::serializeForRollbar($obj, array("myCustomKey"));

        $this->assertArrayNotHasKey("OneTwo", $result);
        $this->assertArrayHasKey("one_two", $result);

        $this->assertArrayHasKey("class", $result);

        $this->assertArrayNotHasKey("PHPUnitTest", $result);
        $this->assertArrayHasKey("php_unit_test", $result);

        $this->assertArrayNotHasKey("my_custom_key", $result);
        $this->assertArrayHasKey("myCustomKey", $result);
        $this->assertNull($result["myCustomKey"]);

        $this->assertArrayNotHasKey("myNullValue", $result);
        $this->assertArrayNotHasKey("my_null_value", $result);
    }
}
