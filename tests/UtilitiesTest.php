<?php namespace Rollbar;

class UtilitiesTest extends \PHPUnit_Framework_TestCase
{
    public function testCoalesce()
    {
        $utilities = new Utilities;
        $this->assertTrue($utilities->coalesce(false, false, true));
        $this->assertNull($utilities->coalesce(false, false));
        $this->assertEquals(5, $utilities->coalesce(false, false, 5));
    }

    public function testPascaleToCamel()
    {
        $toTest = array(
            array("TestMe", "test_me"),
            array("USA", "usa"),
            array("PHPUnit_Framework_TestCase", "php_unit_framework_test_case"),
        );
        foreach ($toTest as $vals) {
            $this->assertEquals($vals[1], Utilities::pascalToCamel($vals[0]));
        }
    }

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
            "OneTwo" => array(1, 2),
            "klass" => "Numbers",
            "PHPUnitTest" => "testSerializeForRollbar",
            "myCustomKey" => null,
            "myNullValue" => null,
        );
        $result = Utilities::serializeForRollbar($obj, array("klass" => "class"), array("myCustomKey"));

        $this->assertArrayNotHasKey("OneTwo", $result);
        $this->assertArrayHasKey("one_two", $result);

        $this->assertArrayNotHasKey("klass", $result);
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
