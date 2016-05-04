<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Payload;

class PayloadTest extends \PHPUnit_Framework_TestCase
{
    public function testPayloadData()
    {
        $data = m::mock("Rollbar\Payload\Data");
        $payload = new Payload($data);

        $this->assertEquals($data, $payload->getData());
    }

    public function testPayloadAccessToken()
    {
        $data = m::mock("Rollbar\Payload\Data");
        ;
        $accessToken = null;

        $payload = new Payload($data, $accessToken);
        $this->assertNull($payload->getAccessToken());

        $accessToken = "too_short";
        try {
            new Payload($data, $accessToken);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "too_longtoo_longtoo_longtoo_longtoo_longtoo_long";
        try {
            new Payload($data, $accessToken);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "012345678901234567890123456789ab";
        $payload = new Payload($data, $accessToken);
        $this->assertEquals($accessToken, $payload->getAccessToken());
    }

    public function testEncode()
    {
        $data = m::mock('Rollbar\Payload\Data, \JsonSerializable')
            ->shouldReceive('jsonSerialize')
            ->andReturn(new \ArrayObject())
            ->mock();
        $payload = new Payload($data);
        $encoded = json_encode($payload);
        $this->assertEquals('{"data":{}}', $encoded);
    }
}
