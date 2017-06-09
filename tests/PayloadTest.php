<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;

class PayloadTest extends BaseUnitTestCase
{
    public function testPayloadData()
    {
        $data = m::mock("Rollbar\Payload\Data");
        $config = m::mock("Rollbar\Config")
                    ->shouldReceive('getAccessToken')
                    ->andReturn('012345678901234567890123456789ab')
                    ->mock();

        $payload = new Payload($data, $config->getAccessToken());

        $this->assertEquals($data, $payload->getData());

        $data2 = m::mock("Rollbar\Payload\Data");
        $this->assertEquals($data2, $payload->setData($data2)->getData());
    }

    public function testPayloadAccessToken()
    {
        $accessToken = "012345678901234567890123456789ab";
        $data = m::mock("Rollbar\Payload\Data");
        $config = m::mock("Rollbar\Config")
                    ->shouldReceive('getAccessToken')
                    ->andReturn($accessToken)
                    ->mock();

        $payload = new Payload($data, $accessToken);
        $this->assertEquals($accessToken, $payload->getAccessToken());

        $accessToken = "too_short";
        $config = m::mock("Rollbar\Config")
                    ->shouldReceive('getAccessToken')
                    ->andReturn($accessToken)
                    ->mock();
        try {
            new Payload($data, $accessToken);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "too_longtoo_longtoo_longtoo_longtoo_longtoo_long";
        $config = m::mock("Rollbar\Config")
                    ->shouldReceive('getAccessToken')
                    ->andReturn($accessToken)
                    ->mock();
        try {
            new Payload($data, $accessToken);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "012345678901234567890123456789ab";
        $config = m::mock("Rollbar\Config")
                    ->shouldReceive('getAccessToken')
                    ->andReturn($accessToken)
                    ->mock();
        $payload = new Payload($data, $accessToken);
        $this->assertEquals($accessToken, $payload->getAccessToken());

        $at2 = "ab012345678901234567890123456789";
        $this->assertEquals($at2, $payload->setAccessToken($at2)->getAccessToken());
    }

    public function testEncode()
    {
        $accessToken = '012345678901234567890123456789ab';
        $data = m::mock('Rollbar\Payload\Data, \JsonSerializable')
            ->shouldReceive('jsonSerialize')
            ->andReturn(new \ArrayObject())
            ->mock();
        $dataBuilder = m::mock('Rollbar\DataBuilder')
            ->shouldReceive('getScrubFields')
            ->andReturn(array())
            ->shouldReceive('scrub')
            ->andReturn(new \ArrayObject())
            ->mock();

        $payload = new Payload($data, $accessToken);
        $encoded = json_encode($payload->jsonSerialize());
        $json = '{"data":{},"access_token":"'.$accessToken.'"}';
        $this->assertEquals($json, $encoded);
    }
}
