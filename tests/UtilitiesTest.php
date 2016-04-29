<?php namespace Rollbar;

use Rollbar\Utilities;

class UtilitiesTest extends \PHPUnit_Framework_TestCase
{
    public function testPascaleToCamel()
    {
        $toTest = array(
            array("TestMe", "test_me"),
            array("USA", "usa"),
            array("PHPUnit_Framework_TestCase", "php_unit_framework_test_case"),
        );
        foreach ($toTest as $vals) {
            $this->assertEquals($vals[1], Utilities::pascaleToCamel($vals[0]));
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
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$null must not be null");
        }

        try {
            Utilities::validateString(1, "number");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$number must be a string");
        }

        try
        {
            Utilities::validateString("1", "str", 2);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), "\$str must be 2 characters long, was '1'");
        }
    }
}
