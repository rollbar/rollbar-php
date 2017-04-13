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
    
    private function scrubTestHelper($config = array(), $context = array())
    {
        $scrubFields = array('sensitive');
        
        $defaultConfig = array(
            'access_token' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        );
        
        $config = new Config(array_replace_recursive($defaultConfig, $config));

        $dataBuilder = new DataBuilder($config->getConfigArray());

        $data = $dataBuilder->makeData(Level::fromName('error'), "testing", $context);
        
        $payload = new Payload($data, $config->getAccessToken(), $config);

        $result = $payload->jsonSerialize();
        
        return $result;
    }
    
    /**
     * @param string $dataName Human-readable name of the type of data under test
     * @param array $result The result of the code under test
     * @param string $scrubField The key that will be asserted for scrubbing
     * @param boolean $recursive Check recursive scrubbing
     * @param string $replacement Character used for scrubbing
     */
    private function scrubTestAssert(
        $dataName,
        $result,
        $scrubField = 'sensitive',
        $recursive = true,
        $replacement = '*'
    ) {
    
        
        $this->assertEquals(
            str_repeat($replacement, 8),
            $result[$scrubField],
            "$dataName did not get scrubbed."
        );

        if ($recursive) {
            $this->assertEquals(
                str_repeat($replacement, 8),
                $result['recursive'][$scrubField],
                "$dataName did not get scrubbed recursively."
            );
        }
    }
    
    public function scrubDataProvider()
    {
        return array(
            array( // test 1
                array( // $testData
                    'nonsensitive' => 'value 1',
                    'sensitive' => 'value 2',
                    'recursive' => array(
                        'sensitive' => 'value 1',
                        'nonsensitive' => 'value 2'
                    )
                )
            )
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testScrubGET($testData)
    {
        $_GET = $testData;
        $result = $this->scrubTestHelper();
        $this->scrubTestAssert('$_GET', $result['data']['request']['GET']);
    }
    
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testGetRequestScrubPOST($testData)
    {
        $_POST = $testData;
        $result = $this->scrubTestHelper();
        $this->scrubTestAssert('$_POST', $result['data']['request']['POST']);
    }

    /**
     * @dataProvider scrubDataProvider
     */
    public function testGetRequestScrubSession($testData)
    {
        $_SESSION = $testData;
        $result = $this->scrubTestHelper();
        $this->scrubTestAssert('$_SESSION', $result['data']['request']['session']);
    }
    
    public function testGetScrubbedHeaders()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'text/html; charset=utf-8';
        $_SERVER['HTTP_SENSITIVE'] = 'Scrub this';
        
        $scrubField = 'Sensitive';
        
        $result = $this->scrubTestHelper(array('scrub_fields' => array($scrubField)));
        $this->scrubTestAssert(
            'Headers',
            $result['data']['request']['headers'],
            $scrubField, // field names in headers are slight different convenstion
            false // non-recursive
        );
    }

    /**
     * @dataProvider scrubDataProvider
     */
    public function testGetRequestScrubExtras($testData)
    {
        $extras = array(
            'extraField1' => $testData
        );
        
        $result = $this->scrubTestHelper(array('requestExtras' => $extras));
        
        $this->scrubTestAssert(
            "Request extras",
            $result['data']['request']['extraField1']
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testMakeDataScrubServerExtras($testData)
    {
        $extras = array(
            'extraField1' => $testData
        );
        
        $result = $this->scrubTestHelper(array('serverExtras' => $extras));
        
        $this->scrubTestAssert(
            "Server extras",
            $result['data']['server']['extraField1']
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testMakeDataScrubCustom($testData)
    {
        $custom = $testData;
        $result = $this->scrubTestHelper(array('custom' => $custom));

        $this->scrubTestAssert(
            "Custom",
            $result['data']['custom']
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testGetRequestScrubBodyContext($testData)
    {
        $bodyContext = array(
            'context1' => $testData
        );
        
        $result = $this->scrubTestHelper(
            array('custom' => $bodyContext),
            $bodyContext
        );

        $this->scrubTestAssert(
            "Request body context",
            $result['data']['body']['message']['context1']
        );
    }
    
    public function scrubQueryStringDataProvider()
    {
        $data = $this->scrubDataProvider();
        
        foreach ($data as &$test) {
            $test[0] = http_build_query($test[0]);
        }
        
        return $data;
    }
    
    /**
     * @dataProvider scrubQueryStringDataProvider
     */
    public function testGetUrlScrub($testData)
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_URI'] = "/index.php?$testData";
        
        $result = $this->scrubTestHelper();
        $parsed = array();
        parse_str(parse_url($result['data']['request']['url'], PHP_URL_QUERY), $parsed);

        $this->scrubTestAssert(
            "Url",
            $parsed,
            'sensitive',
            true,
            'x' // query string is scrubbed with "x" rather than "*"
        );
    }
    
    /**
     * @dataProvider scrubQueryStringDataProvider
     */
    public function testGetRequestScrubQueryString($testData)
    {
        $_SERVER['QUERY_STRING'] = "?$testData";
        
        $result = $this->scrubTestHelper();
        $parsed = array();
        parse_str($result['data']['request']['query_string'], $parsed);
        
        $this->scrubTestAssert(
            "Query string",
            $parsed,
            'sensitive',
            true,
            'x' // query string is scrubbed with "x" rather than "*"
        );
    }
}
