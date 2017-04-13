<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;

class PayloadTest extends \PHPUnit_Framework_TestCase
{
    public function testPayloadData()
    {
        $data = m::mock("Rollbar\Payload\Data");
        $config = m::mock("Rollbar\Config");
        
        $payload = new Payload($data, "012345678901234567890123456789ab", $config);

        $this->assertEquals($data, $payload->getData());

        $data2 = m::mock("Rollbar\Payload\Data");
        $this->assertEquals($data2, $payload->setData($data2)->getData());
    }

    public function testPayloadAccessToken()
    {
        $data = m::mock("Rollbar\Payload\Data");
        $config = m::mock("Rollbar\Config");
        $accessToken = "012345678901234567890123456789ab";

        $payload = new Payload($data, $accessToken, $config);
        $this->assertEquals($accessToken, $payload->getAccessToken());

        $accessToken = "too_short";
        try {
            new Payload($data, $accessToken, $config);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "too_longtoo_longtoo_longtoo_longtoo_longtoo_long";
        try {
            new Payload($data, $accessToken, $config);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "012345678901234567890123456789ab";
        $payload = new Payload($data, $accessToken, $config);
        $this->assertEquals($accessToken, $payload->getAccessToken());

        $at2 = "ab012345678901234567890123456789";
        $this->assertEquals($at2, $payload->setAccessToken($at2)->getAccessToken());
    }

    public function testEncode()
    {
        $data = m::mock('Rollbar\Payload\Data, \JsonSerializable')
            ->shouldReceive('jsonSerialize')
            ->andReturn(new \ArrayObject())
            ->mock();
        $dataBuilder = m::mock('Rollbar\DataBuilder')
            ->shouldReceive('getScrubFields')
            ->andReturn(array())
            ->mock();
        $config = m::mock("Rollbar\Config")
            ->shouldReceive('getDataBuilder')
            ->andReturn($dataBuilder)
            ->mock();
        
        $payload = new Payload($data, "012345678901234567890123456789ab", $config);
        $encoded = json_encode($payload->jsonSerialize());
        $json = '{"data":{},"access_token":"012345678901234567890123456789ab"}';
        $this->assertEquals($json, $encoded);
    }
    
    public function testScrubGET()
    {
        $_GET['Secret data'] = 'Secret value';

        $scrubFields = array('Secret data');
        
        $config = new Config(array(
            'access_token' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $dataBuilder = new DataBuilder($config->getConfigArray());

        $data = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        
        $payload = new Payload($data, $config->getAccessToken(), $config);

        $result = $payload->jsonSerialize();
        
        $this->assertEquals('********', $result['data']['request']['GET']['Secret data'], "GET arguments of the request did not get scrubbed.");
    }
}
