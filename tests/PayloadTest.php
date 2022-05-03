<?php namespace Rollbar;

use Mockery as m;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;
use Rollbar\DataBuilder;
use Rollbar\Config;
use Rollbar\Payload\Data;

class PayloadTest extends BaseRollbarTest
{
    public function testPayloadData(): void
    {
        $data = m::mock(Data::class);
        $config = m::mock(Config::class)
                    ->shouldReceive('getAccessToken')
                    ->andReturn('012345678901234567890123456789ab')
                    ->mock();
        
        $payload = new Payload($data, $config->getAccessToken());

        $this->assertEquals($data, $payload->getData());

        $data2 = m::mock(Data::class);
        $this->assertEquals($data2, $payload->setData($data2)->getData());
    }

    public function testPayloadAccessToken(): void
    {
        $accessToken = "012345678901234567890123456789ab";
        $data = m::mock(Data::class);
        $config = m::mock(Config::class)
                    ->shouldReceive('getAccessToken')
                    ->andReturn($accessToken)
                    ->mock();

        $payload = new Payload($data, $accessToken);
        $this->assertEquals($accessToken, $payload->getAccessToken());

        $accessToken = "012345678901234567890123456789ab";
        $config = m::mock(Config::class)
                    ->shouldReceive('getAccessToken')
                    ->andReturn($accessToken)
                    ->mock();
        $payload = new Payload($data, $accessToken);
        $this->assertEquals($accessToken, $payload->getAccessToken());

        $at2 = "ab012345678901234567890123456789";
        $this->assertEquals($at2, $payload->setAccessToken($at2)->getAccessToken());
    }

    public function testEncode(): void
    {
        $accessToken = $this->getTestAccessToken();
        $data = m::mock('Rollbar\Payload\Data, Rollbar\SerializerInterface')
            ->shouldReceive('serialize')
            ->andReturn(new \ArrayObject())
            ->mock();
        m::mock(DataBuilder::class)
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
