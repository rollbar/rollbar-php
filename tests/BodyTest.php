<?php namespace Rollbar;

use Mockery as m;
use Rollbar\Payload\Body;
use Rollbar\Payload\ContentInterface;

class BodyTest extends BaseRollbarTest
{
    public function testBodyValue(): void
    {
        $value = m::mock(ContentInterface::class);
        $body = new Body($value);
        $this->assertEquals($value, $body->getValue());

        $mock2 = m::mock(ContentInterface::class);
        $this->assertEquals($mock2, $body->setValue($mock2)->getValue());
    }

    public function testExtra(): void
    {
        $value = m::mock(ContentInterface::class)
            ->shouldReceive("serialize")
            ->andReturn("{CONTENT}")
            ->shouldReceive("getKey")
            ->andReturn("content_interface")
            ->mock();
        $expected = array(
            "hello" => "world"
        );
        $body = new Body($value, $expected);
        $this->assertEquals($body->getExtra(), $expected);
    }

    public function testSerialize(): void
    {
        $value = m::mock(ContentInterface::class)
            ->shouldReceive("serialize")
            ->andReturn("{CONTENT}")
            ->shouldReceive("getKey")
            ->andReturn("content_interface")
            ->mock();
        $body = new Body($value, array('foo' => 'bar'));
        $encoded = json_encode($body->serialize());
        $this->assertEquals(
            "{\"content_interface\":\"{CONTENT}\",\"extra\":{\"foo\":\"bar\"}}",
            $encoded
        );
    }

    public function testSerializeWithMaxNestingDepth(): void
    {
        $value = m::mock(ContentInterface::class)
            ->shouldReceive("serialize")
            ->andReturn("{CONTENT}")
            ->shouldReceive("getKey")
            ->andReturn("content_interface")
            ->mock();
        
        // Create deeply nested array that would cause memory issues
        $deepArray = array('level1' => array('level2' => array('level3' => array('level4' => 'deep_value'))));
        
        // Test without depth limit - should serialize completely
        $bodyNoLimit = new Body($value, array('deep' => $deepArray), null, -1);
        $resultNoLimit = $bodyNoLimit->serialize();
        
        // Test with depth limit - should truncate deep nesting
        $bodyWithLimit = new Body($value, array('deep' => $deepArray), null, 2);
        $resultWithLimit = $bodyWithLimit->serialize();
        
        // Verify basic structure exists
        $this->assertArrayHasKey('extra', $resultNoLimit);
        $this->assertArrayHasKey('extra', $resultWithLimit);
        
        // Without limit should have all nested levels
        $this->assertEquals('deep_value', $resultNoLimit['extra']['deep']['level1']['level2']['level3']['level4']);
        
        // With limit should truncate the 'deep' array due to depth constraint
        // At depth 2: root -> extra -> deep (gets truncated to empty array)
        $this->assertArrayHasKey('deep', $resultWithLimit['extra']);
        $this->assertEmpty($resultWithLimit['extra']['deep']); // Truncated due to depth limit
    }
}
