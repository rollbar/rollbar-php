<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;

class PayloadTest extends BaseRollbarTest
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
        $accessToken = $this->getTestAccessToken();
        $data = m::mock('Rollbar\Payload\Data, \Serializable')
            ->shouldReceive('serialize')
            ->andReturn(new \ArrayObject())
            ->mock();
        m::mock('Rollbar\DataBuilder')
            ->shouldReceive('getScrubFields')
            ->andReturn(array())
            ->shouldReceive('scrub')
            ->andReturn(new \ArrayObject())
            ->mock();
        
        $payload = new Payload($data, $accessToken);
        $encoded = json_encode($payload->serialize());
        $json = '{"data":{},"access_token":"'.$accessToken.'"}';
        $this->assertEquals($json, $encoded);
    }
}
