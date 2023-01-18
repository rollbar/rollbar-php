<?php namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\TestHelpers\CustomSerializable;
use Rollbar\TestHelpers\CycleCheck\ParentCycleCheck;
use Rollbar\TestHelpers\CycleCheck\ChildCycleCheck;
use Rollbar\TestHelpers\CycleCheck\ParentCycleCheckSerializable;
use Rollbar\TestHelpers\CycleCheck\ChildCycleCheckSerializable;
use Rollbar\TestHelpers\DeprecatedSerializable;

class UtilitiesTest extends BaseRollbarTest
{
    public function testValidateString(): void
    {
        Utilities::validateString("");
        Utilities::validateString("true");
        Utilities::validateString("four", "local", 4);
        Utilities::validateString(null);

        try {
            Utilities::validateString(null, "null", null, false);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("\$null must not be null", $e->getMessage());
        }

        try {
            Utilities::validateString(1, "number");
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("\$number must be a string", $e->getMessage());
        }

        try {
            Utilities::validateString("1", "str", 2);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("\$str must be 2 characters long, was '1'", $e->getMessage());
        }
    }

    public function testValidateInteger(): void
    {
        Utilities::validateInteger(null);
        Utilities::validateInteger(0);
        Utilities::validateInteger(1, "one", 0, 2);

        try {
            Utilities::validateInteger(null, "null", null, null, false);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("\$null must not be null", $e->getMessage());
        }

        try {
            Utilities::validateInteger(0, "zero", 1);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("\$zero must be >= 1", $e->getMessage());
        }

        try {
            Utilities::validateInteger(0, "zero", null, -1);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("\$zero must be <= -1", $e->getMessage());
        }
    }

    public function testValidateBooleanThrowsExceptionOnNullWhenNullAreNotAllowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Utilities::validateBoolean(null, "foo", false);
    }

    public function testValidateBooleanWithInvalidBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Utilities::validateBoolean("not a boolean");
    }

    public function testValidateBoolean(): void
    {
        Utilities::validateBoolean(true, "foo", false);
        Utilities::validateBoolean(true);
        Utilities::validateBoolean(null);
        $this->expectNotToPerformAssertions();
    }

    public function testSerializeForRollbar(): void
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
    
    public function testSerializationCycleChecking(): void
    {
        $config = new Config(array("access_token"=>$this->getTestAccessToken()));
        $data = $config->getRollbarData(\Rollbar\Payload\Level::WARNING, "String", array(new ParentCycleCheck()));
        $payload = new \Rollbar\Payload\Payload($data, $this->getTestAccessToken());
        $obj = array(
            "one_two" => array(1, 2),
            "payload" => $payload,
            "obj" => new ParentCycleCheck(),
            "serializedObj" => new ParentCycleCheckSerializable(),
        );
        $objectHashes = array();
        
        $result = Utilities::serializeForRollbar($obj, null, $objectHashes);
        
        $this->assertMatchesRegularExpression(
            '/<CircularReference.*/',
            $result["obj"]["value"]["child"]["value"]["parent"]
        );
        
        $this->assertMatchesRegularExpression(
            '/<CircularReference.*/',
            $result["serializedObj"]["child"]["parent"]
        );
        
        $this->assertMatchesRegularExpression(
            '/<CircularReference.*/',
            $result["payload"]["data"]["body"]["extra"][0]["value"]["child"]["value"]["parent"]
        );
    }

    public function testSerializeForRollbarNestingLevels(): void
    {
        $obj = array(
            "one" => array(
                'two' => array(
                    'three' => array(
                        'four' => array(1, 2),
                    ),
                ),
            ),
        );
        
        $objectHashes = array();
        $result = Utilities::serializeForRollbar($obj, null, $objectHashes, 2);
        $this->assertArrayHasKey('one', $result);
        $this->assertArrayHasKey('two', $result['one']);
        $this->assertArrayNotHasKey('three', $result['one']['two']);

        $objectHashes = array();
        $result = Utilities::serializeForRollbar($obj, null, $objectHashes, 3);
        $this->assertArrayHasKey('one', $result);
        $this->assertArrayHasKey('two', $result['one']);
        $this->assertArrayHasKey('three', $result['one']['two']);
        $this->assertArrayNotHasKey('four', $result['one']['two']['three']);

        $result = Utilities::serializeForRollbar($obj);
        $this->assertArrayHasKey('one', $result);
        $this->assertArrayHasKey('two', $result['one']);
        $this->assertArrayHasKey('three', $result['one']['two']);
        $this->assertArrayHasKey('four', $result['one']['two']['three']);
    }

    public function testSerializationOfDeprecatedSerializable()
    {
        $data = ['foo' => 'bar'];

        $obj = array(
            "serializedObj" => new DeprecatedSerializable($data),
        );
        $objectHashes = array();

        // Make sure the deprecation notice is sent if the object implements deprecated Serializable interface
        set_error_handler(function (
            int $errno,
            string $errstr,
        ) : bool {
            $this->assertStringContainsString("Serializable", $errstr);
            $this->assertStringContainsString("deprecated", $errstr);
            return true;
        }, E_USER_DEPRECATED);

        $result = Utilities::serializeForRollbar($obj, null, $objectHashes);

        // Clear the handler, so it does not mess with other tests.
        restore_error_handler();

        $this->assertEquals(['foo' => 'bar'], $result['serializedObj']);
    }

    public function testSerializationOfCustomSerializable()
    {
        $data = ['foo' => 'bar'];

        $obj = array(
            "serializedObj" => new CustomSerializable($data),
        );
        $objectHashes = array();

        // Make sure the deprecation notice is NOT sent if the object implements Serializable but it's using
        // __serialize and __unserialize properly
        set_error_handler(function (
            int $errno,
            string $errstr,
        ) : bool {
            $this->assertStringNotContainsString("Serializable", $errstr);
            $this->assertStringNotContainsString("deprecated", $errstr);
            return true;
        }, E_USER_DEPRECATED);

        $result = Utilities::serializeForRollbar($obj, null, $objectHashes);

        // Clear the handler, so it does not mess with other tests.
        restore_error_handler();

        $this->assertEquals(['foo' => 'bar'], $result['serializedObj']);
    }
}
