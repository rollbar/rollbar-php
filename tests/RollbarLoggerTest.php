<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use Rollbar\TestHelpers\Exceptions\SilentExceptionSampleRate;
use Psr\Log\LogLevel as PsrLogLevel;

class RollbarLoggerTest extends BaseRollbarTest
{
    
    public function setUp()
    {
        $_SESSION = array();
        parent::setUp();
    }
    
    public function tearDown()
    {
        Rollbar::destroy();
        parent::tearDown();
    }
    
    public function testAddCustom()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        
        $logger->addCustom("foo", "bar");
        
        $dataBuilder = $logger->getDataBuilder();
        
        $result = $dataBuilder->makeData(
            Level::INFO,
            "This test message should have custom data attached.",
            array()
        );
        
        $customData = $result->getCustom();
        $this->assertEquals("bar", $customData["foo"]);
        
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "custom" => array(
                "baz" => "xyz"
            )
        ));
        
        $logger->addCustom("foo", "bar");
        
        $dataBuilder = $logger->getDataBuilder();
        
        $result = $dataBuilder->makeData(
            Level::INFO,
            "This test message should have custom data attached.",
            array()
        );
        
        $customData = $result->getCustom();
        $this->assertEquals("bar", $customData["foo"]);
    }
    
    public function testRemoveCustom()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "custom" => array(
                "foo" => "bar",
                "bar" => "xyz"
            )
        ));
        
        $logger->removeCustom("foo");
        
        $dataBuilder = $logger->getDataBuilder();
        
        $result = $dataBuilder->makeData(
            Level::INFO,
            "This test message should have custom data attached.",
            array()
        );
        
        $customData = $result->getCustom();
        $this->assertFalse(isset($customData["foo"]));
        $this->assertEquals("xyz", $customData["bar"]);
    }
    
    public function testGetCustom()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "custom" => array(
                "foo" => "bar",
                "bar" => "xyz"
            )
        ));
        
        $custom = $logger->getCustom();
        
        $this->assertEquals("bar", $custom["foo"]);
        $this->assertEquals("xyz", $custom["bar"]);
    }

    public function testConfigure()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $l->configure(array("extraData" => 15));
        $extended = $l->scope(array())->extend(array());
        $this->assertEquals(15, $extended['extraData']);
    }

    public function testLog()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $response = $l->log(Level::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(200, $response->getStatus());
    }

    public function testNotLoggingPayload()
    {
        $logPayloadLoggerMock = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
        $logPayloadLoggerMock->expects($this->never())->method('debug');

        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "log_payload" => false,
            "log_payload_logger" => $logPayloadLoggerMock
        ));
        $response = $logger->log(Level::WARNING, "Testing PHP Notifier");
        
        $this->assertEquals(200, $response->getStatus());
    }

    public function testDefaultVerbose()
    {
        return $this->testNotVerbose();
    }

    public function testNotVerbose()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "verbose" => \Rollbar\Config::VERBOSE_NONE
        ));

        $verboseLogger = $logger->verboseLogger();
        $originalHandler = $verboseLogger->getHandlers();
        $originalHandler = $originalHandler[0];

        $handlerMock = $this->getMockBuilder('\Monolog\Handler\ErrorLogHandler')
            ->setMethods(array('handle'))
            ->getMock();

        $handlerMock->setLevel($originalHandler->getLevel());
        
        $handlerMock->expects($this->never())->method('handle');

        $verboseLogger->setHandlers(array($handlerMock));

        $logger->info('Internal message');
    }

    public function testVerbose()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "verbose" => \Psr\Log\LogLevel::DEBUG
        ));

        $verboseLogger = $logger->verboseLogger();
        $originalHandler = $verboseLogger->getHandlers();
        $originalHandler = $originalHandler[0];

        $handlerMock = $this->getMockBuilder('\Monolog\Handler\ErrorLogHandler')
            ->setMethods(array('handle'))
            ->getMock();
        $handlerMock->setLevel($originalHandler->getLevel());

        $handlerMock->expects($this->atLeastOnce())->method('handle');

        $verboseLogger->setHandlers(array($handlerMock));

        $logger->info('Internal message');
    }
    
    public function testEnabled()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        $response = $logger->log(Level::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(200, $response->getStatus());
        
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "enabled" => false
        ));
        $response = $logger->log(Level::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Disabled", $response->getInfo());
    }

    public function testTransmit()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $response = $logger->log(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(200, $response->getStatus());

        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "transmit" => false
        ));
        $response = $logger->log(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Not transmitting (transmitting disabled in configuration)", $response->getInfo());
    }

    public function testTransmitBatched()
    {
        $senderMock = $this->getMockBuilder('Rollbar\Senders\SenderInterface')->getMock();
        $senderMock->expects($this->once())->method('sendBatch');

        // transmit on (default)
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "batched" => true,
            "sender" => $senderMock
        ));
        $response = $logger->log(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Pending", $response->getInfo());
        $logger->flush();

        // transmit off
        $senderMock = $this->getMockBuilder('Rollbar\Senders\SenderInterface')->getMock();
        $senderMock->expects($this->never())->method('sendBatch');

        $logger->configure(array(
            'transmit' => false,
            'sender' => $senderMock
        ));

        $response = $logger->log(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Pending", $response->getInfo());
        $response = $logger->flush();
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Not transmitting (transmitting disabled in configuration)", $response->getInfo());
    }
    
    public function testLogMalformedPayloadData()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "transformer" => '\Rollbar\TestHelpers\MalformedPayloadDataTransformer'
        ));
        
        $response = $logger->log(
            Level::ERROR,
            "Forced payload's data to false value.",
            array()
        );
        
        $this->assertEquals(400, $response->getStatus());
    }

    public function testFlush()
    {
        $senderMock = $this->getMockBuilder('Rollbar\Senders\SenderInterface')->getMock();
        $senderMock->expects($this->once())->method('sendBatch')->with(
            $this->containsOnlyInstancesOf('Rollbar\Payload\EncodedPayload'),
            $this->anything()
        );

        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "batched" => true,
            "sender" => $senderMock
        ));

        $response = $logger->flush();
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Queue empty", $response->getInfo());

        $response = $logger->log(Level::INFO, "Batched message");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Pending", $response->getInfo());

        $response = $logger->flush();
    }
    
    public function testContext()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $response = $l->log(
            Level::ERROR,
            new \Exception("Testing PHP Notifier"),
            array(
                "foo" => "bar"
            )
        );
        $this->assertEquals(200, $response->getStatus());
    }
    
    public function testLogStaticLevel()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $response = $l->log(Level::warning(), "Testing PHP Notifier", array());
        $this->assertEquals(200, $response->getStatus());
    }

    public function testErrorSampleRates()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "error_sample_rates" => array(
                E_ERROR => 0
            )
        ));
        $response = $l->log(
            Level::ERROR,
            new ErrorWrapper(
                E_ERROR,
                '',
                null,
                null,
                array(),
                new Utilities
            ),
            array()
        );
        $this->assertEquals(0, $response->getStatus());
    }

    public function testExceptionSampleRates()
    {
        $l = new RollbarLogger(array(
            "access_token" => "ad865e76e7fb496fab096ac07b1dbabb",
            "environment" => "testing-php",
            "exception_sample_rates" => array(
                'Rollbar\TestHelpers\Exceptions\SilentExceptionSampleRate' => 0.0
            )
        ));
        $response = $l->log(Level::ERROR, new SilentExceptionSampleRate);
        
        $this->assertEquals(0, $response->getStatus());
        
        $response = $l->log(Level::ERROR, new \Exception);
        
        $this->assertEquals(200, $response->getStatus());
    }

    public function testIncludedErrNo()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "included_errno" => E_ERROR | E_WARNING
        ));
        $response = $l->log(
            Level::ERROR,
            new ErrorWrapper(
                E_USER_ERROR,
                '',
                null,
                null,
                array(),
                new Utilities
            ),
            array()
        );
        $this->assertEquals(0, $response->getStatus());
    }
    
    private function scrubTestHelper($config = array(), $context = array())
    {
        $scrubFields = array('sensitive');
        
        $defaultConfig = array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        );
        
        $config = new Config(array_replace_recursive($defaultConfig, $config));

        $dataBuilder = $config->getDataBuilder();
        $data = $dataBuilder->makeData(Level::ERROR, "testing", $context);
        $payload = new Payload($data, $config->getAccessToken());

        $scrubbed = $payload->serialize();
        $scrubber = $config->getScrubber();

        $result = $scrubber->scrub($scrubbed);
        
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
    public function testMakeDataScrubPerson($testData)
    {
        $testData['id'] = '123';
        $result = $this->scrubTestHelper(
            array(
                'person' => $testData,
                'scrub_whitelist' => array(
                    'data.person.recursive.sensitive'
                )
            )
        );
        
        $this->assertEquals(
            str_repeat('*', 8),
            $result['data']['person']['sensitive'],
            "Person did not get scrubbed."
        );
        
        $this->assertNotEquals(
            str_repeat('*', 8),
            $result['data']['person']['recursive']['sensitive'],
            "Person recursive.sensitive DID get scrubbed even though it's whitelisted."
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
            $result['data']['body']['extra']['context1']
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

    public function testPsr3Emergency()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->emergency("Testing PHP Notifier");
    }

    public function testPsr3Alert()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->alert("Testing PHP Notifier");
    }

    public function testPsr3Critical()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->critical("Testing PHP Notifier");
    }

    public function testPsr3Error()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->error("Testing PHP Notifier");
    }

    public function testPsr3Warning()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->warning("Testing PHP Notifier");
    }

    public function testPsr3Notice()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->notice("Testing PHP Notifier");
    }

    public function testPsr3Info()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->info("Testing PHP Notifier");
    }

    public function testPsr3Debug()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->debug("Testing PHP Notifier");
    }
    
    /**
     * @dataProvider maxItemsProvider
     */
    public function testMaxItems($maxItemsConfig)
    {
        $config = array('access_token' => $this->getTestAccessToken());
        if ($maxItemsConfig !== null) {
            $config['max_items'] = $maxItemsConfig;
        }
        
        Rollbar::init($config);
        $logger = Rollbar::logger();
        
        $maxItems = $maxItemsConfig === null ? Defaults::get()->maxItems() : $maxItemsConfig;
        
        for ($i = 0; $i < $maxItems; $i++) {
            $response = $logger->log(Level::INFO, 'testing info level');
            $this->assertEquals(200, $response->getStatus());
        }
      
        $response = $logger->log(Level::INFO, 'testing info level');
        
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals(
            "Maximum number of items per request has been reached. If you " .
            "want to report more items, please use `max_items` " .
            "configuration option.",
            $response->getInfo()
        );
    }
    
    public function maxItemsProvider()
    {
        return array(
            'use default max_items' => array(null),
            'use provided max_items' => array(3)
        );
    }
    
    /**
     * @expectedException Exception
     */
    public function testRaiseOnError()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => 'test',
            "raise_on_error" => true
        ));
        
        try {
            throw new \Exception();
        } catch (\Exception $ex) {
            $logger->log(Level::ERROR, $ex);
        }
    }
}
