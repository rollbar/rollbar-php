<?php namespace Rollbar;

use Rollbar\TestHelpers\ArrayLogger;
use Rollbar\TestHelpers\CustomSerializable;
use Rollbar\TestHelpers\CycleCheck\ParentCycleCheck;
use Rollbar\TestHelpers\CycleCheck\ParentCycleCheckSerializable;
use Rollbar\TestHelpers\CycleCheck\ChildCycleCheckSerializable;
use Rollbar\TestHelpers\DeprecatedSerializable;
use Rollbar\TestHelpers\RandomBytesStub;

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

        try {
            Utilities::validateString("foo", "str", [2, 4]);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("\$str must be 2, 4 characters long, was 'foo'", $e->getMessage());
        }
        Utilities::validateString("four", "local", [2, 4]);
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

    public function testSerializeToArray(): void
    {
        $this->assertSame(array('type' => 'NULL'), Utilities::serializeToArray(null));
        $this->assertSame(array('type' => 'integer'), Utilities::serializeToArray(4));
        $this->assertSame(array(1, 2), Utilities::serializeToArray(array(1, 2)));
        $this->assertSame(
            array('foo' => 'bar'),
            Utilities::serializeToArray(new CustomSerializable(array('foo' => 'bar'))),
        );
        $this->assertSame(
            array(
                'type' => 'object',
                'class' => 'Rollbar\TestHelpers\ArrayLogger'
            ),
            Utilities::serializeToArray(new ArrayLogger()),
        );
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
            $result["obj"]["value"]["child"]["value"]["parent"],
        );

        $this->assertMatchesRegularExpression(
            '/<CircularReference.*/',
            $result["serializedObj"]["child"]["parent"],
        );

        $this->assertMatchesRegularExpression(
            '/<CircularReference.*/',
            $result["payload"]["data"]["body"]["extra"][0]["value"]["child"]["value"]["parent"],
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

    public function testUuid4ReturnsVersion4Uuid(): void
    {
        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/',
            Utilities::uuid4()
        );
    }

    /**
     * @dataProvider uuid4RandomBytesProvider
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUuid4UsesSecureRandomBytes(string $bytes, string $expectedUuid): void
    {
        $requestedLengths = [];
        RandomBytesStub::$callback = static function (int $length) use ($bytes, &$requestedLengths): string {
            $requestedLengths[] = $length;
            return $bytes;
        };

        $this->assertSame($expectedUuid, Utilities::uuid4());
        $this->assertSame([16], $requestedLengths);
    }

    public static function uuid4RandomBytesProvider(): array
    {
        return [
            'zero bits' => [str_repeat("\x00", 16), '00000000-0000-4000-8000-000000000000'],
            'one bits' => [str_repeat("\xff", 16), 'ffffffff-ffff-4fff-bfff-ffffffffffff'],
            'mixed bits' => [
                hex2bin('0011223344556677a899aabbccddeeff'),
                '00112233-4455-4677-a899-aabbccddeeff',
            ],
        ];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUuid4DoesNotChangeMtRandStateWhenSecureRandomnessIsAvailable(): void
    {
        RandomBytesStub::$callback = static function (int $length): string {
            return str_repeat("\x00", $length);
        };

        \mt_srand(12345);
        $expected = \mt_rand();

        \mt_srand(12345);
        Utilities::uuid4();

        $this->assertSame($expected, \mt_rand());
    }

    /**
     * @param class-string<\Exception> $exceptionClass
     * @dataProvider uuid4RandomnessExceptionProvider
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUuid4FallsBackWithoutReseedingWhenSecureRandomnessFails(string $exceptionClass): void
    {
        $requestedLengths = [];
        RandomBytesStub::$callback = static function (int $length) use ($exceptionClass, &$requestedLengths): string {
            $requestedLengths[] = $length;
            throw new $exceptionClass('No secure randomness available.');
        };

        \mt_srand(12345);
        $firstUuid = Utilities::uuid4();
        $secondUuid = Utilities::uuid4();

        foreach ([$firstUuid, $secondUuid] as $uuid) {
            $this->assertMatchesRegularExpression(
                '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/',
                $uuid
            );
        }
        $this->assertNotSame($firstUuid, $secondUuid);

        // Replaying the seed must reproduce the sequence without uuid4() reseeding it.
        \mt_srand(12345);
        $this->assertSame($firstUuid, Utilities::uuid4());
        $this->assertSame($secondUuid, Utilities::uuid4());
        $this->assertSame([16, 16, 16, 16], $requestedLengths);
    }

    public static function uuid4RandomnessExceptionProvider(): array
    {
        return [
            'generic exception' => [\Exception::class],
            'random exception' => [\Random\RandomException::class],
        ];
    }
}
