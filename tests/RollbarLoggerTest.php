<?php namespace Rollbar;

use Exception;
use Monolog\Handler\NoopHandler;
use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use Rollbar\TestHelpers\ArrayLogger;
use Rollbar\TestHelpers\Exceptions\SilentExceptionSampleRate;
use StdClass;
use Rollbar\Payload\EncodedPayload;
use Rollbar\Senders\SenderInterface;
use Rollbar\TestHelpers\MalformedPayloadDataTransformer;
use Monolog\Handler\ErrorLogHandler;
use Psr\Log\LoggerInterface;

class RollbarLoggerTest extends BaseRollbarTest
{
    
    public function setUp(): void
    {
        $_SESSION = array();
        parent::setUp();
    }
    
    public function tearDown(): void
    {
        Rollbar::destroy();
        parent::tearDown();
    }
    
    public function testAddCustom(): void
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
    
    public function testRemoveCustom(): void
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
    
    public function testGetCustom(): void
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

    public function testConfigure(): void
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $l->configure(array("extraData" => 15));
        $extended = $l->scope(array())->extend(array());
        $this->assertEquals(15, $extended['extraData']);
    }

    public function testLog(): void
    {
        // Array logger collects the verbose logs, so we can assert the log was
        // successfully sent to the Rollbar service.
        $verbose = new ArrayLogger();
        $logger  = new RollbarLogger([
            "access_token"   => $this->getTestAccessToken(),
            "environment"    => "testing-php",
            "verbose_logger" => $verbose,
        ]);

        $this->assertNotContains('[info]Occurrence successfully logged', $verbose->logs);
        $logger->log(Level::WARNING, "Testing PHP Notifier", []);
//        print_r($array->logs);
        $this->assertContains('[info]Attempting to log: [' . Level::WARNING . '] Testing PHP Notifier', $verbose->logs);
        $this->assertContains('[info]Occurrence successfully logged', $verbose->logs);
    }

    public function testNotLoggingPayload(): void
    {
        // Array logger collects the verbose logs, so we can assert the log was
        // successfully sent to the Rollbar service.
        $verbose              = new ArrayLogger();
        $logPayloadLoggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logPayloadLoggerMock->expects($this->never())->method('debug');

        $logger = new RollbarLogger([
            "access_token"       => $this->getTestAccessToken(),
            "environment"        => "testing-php",
            "log_payload"        => false,
            "log_payload_logger" => $logPayloadLoggerMock,
            "verbose_logger"     => $verbose,
        ]);

        $this->assertSame(0, $verbose->count(Level::INFO, 'Occurrence successfully logged'));
        $logger->log(Level::WARNING, "Testing PHP Notifier", []);
        $this->assertSame(
            1,
            $verbose->count(Level::INFO, 'Attempting to log: [' . Level::WARNING . '] Testing PHP Notifier'),
        );
        $this->assertSame(1, $verbose->count(Level::INFO, 'Occurrence successfully logged'));
    }

    public function testReport(): void
    {
        $logger = new RollbarLogger([
            "access_token" => $this->getTestAccessToken(),
            "environment"  => "testing-php",
        ]);

        $response = $logger->report(Level::WARNING, "Testing PHP Notifier", []);
        $this->assertEquals(200, $response->getStatus());
    }

    public function testReportWithIsUncaught(): void
    {
        $test = $this;
        $logger = new RollbarLogger([
            "access_token" => $this->getTestAccessToken(),
            "environment"  => "testing-php",
            'check_ignore' => function ($isUncaught) use ($test) {
                $test::assertTrue($isUncaught);
            },
        ]);

        $response = $logger->report(Level::WARNING, "Testing PHP Notifier", isUncaught: true);
        $this->assertEquals(200, $response->getStatus());
    }

    public function testDefaultVerbose(): void
    {
        $this->testNotVerbose();
    }

    public function testNotVerbose(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "verbose" => \Rollbar\Config::VERBOSE_NONE
        ));

        $this->assertInstanceOf(NoopHandler::class, $logger->verboseLogger()->getHandlers()[0]);
    }

    public function testVerbose(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "verbose" => \Psr\Log\LogLevel::DEBUG
        ));

        $verboseLogger = $logger->verboseLogger();
        $originalHandler = $verboseLogger->getHandlers();
        $originalHandler = $originalHandler[0];

        $handlerMock = $this->getMockBuilder(ErrorLogHandler::class)
            ->setMethods(array('handle'))
            ->getMock();
        $handlerMock->setLevel($originalHandler->getLevel());

        $handlerMock->expects($this->atLeastOnce())->method('handle');

        $verboseLogger->setHandlers(array($handlerMock));

        $logger->info('Internal message');
    }
    
    public function testEnabled(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        $response = $logger->report(Level::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(200, $response->getStatus());
        
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "enabled" => false
        ));
        $response = $logger->report(Level::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Disabled", $response->getInfo());
    }

    public function testTransmit(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $response = $logger->report(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(200, $response->getStatus());

        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "transmit" => false
        ));
        $response = $logger->report(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Not transmitting (transmitting disabled in configuration)", $response->getInfo());
    }

    public function testTransmitBatched(): void
    {
        $senderMock = $this->getMockBuilder(SenderInterface::class)->getMock();
        $senderMock->expects($this->once())->method('sendBatch');

        // transmit on (default)
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "batched" => true,
            "sender" => $senderMock
        ));
        $response = $logger->report(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Pending", $response->getInfo());
        $logger->flush();

        // transmit off
        $senderMock = $this->getMockBuilder(SenderInterface::class)->getMock();
        $senderMock->expects($this->never())->method('sendBatch');

        $logger->configure(array(
            'transmit' => false,
            'sender' => $senderMock
        ));

        $response = $logger->report(Level::WARNING, "Testing PHP Notifier");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Pending", $response->getInfo());
        $response = $logger->flush();
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Not transmitting (transmitting disabled in configuration)", $response->getInfo());
    }
    
    public function testLogMalformedPayloadData(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "transformer" => MalformedPayloadDataTransformer::class
        ));
        
        $response = $logger->report(
            Level::ERROR,
            "Forced payload's data to false value.",
            array()
        );
        
        $this->assertEquals(422, $response->getStatus());
    }

    public function testFlush(): void
    {
        $senderMock = $this->getMockBuilder(SenderInterface::class)->getMock();
        $senderMock->expects($this->once())->method('sendBatch')->with(
            $this->containsOnlyInstancesOf(EncodedPayload::class),
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

        $response = $logger->report(Level::INFO, "Batched message");
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals("Pending", $response->getInfo());

        $response = $logger->flush();
    }
    
    public function testContext(): void
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $response = $l->report(
            Level::ERROR,
            new \Exception("Testing PHP Notifier"),
            array(
                "foo" => "bar"
            )
        );
        $this->assertEquals(200, $response->getStatus());
    }
    
    public function testLogStaticLevel(): void
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $response = $l->report(Level::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(200, $response->getStatus());
    }

    public function testErrorSampleRates(): void
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "error_sample_rates" => array(
                E_ERROR => 0
            )
        ));
        $response = $l->report(
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

    public function testExceptionSampleRates(): void
    {
        $l = new RollbarLogger(array(
            "access_token" => "ad865e76e7fb496fab096ac07b1dbabb",
            "environment" => "testing-php",
            "exception_sample_rates" => array(
                SilentExceptionSampleRate::class => 0.0
            )
        ));
        $response = $l->report(Level::ERROR, new SilentExceptionSampleRate);
        
        $this->assertEquals(0, $response->getStatus());
        
        $response = $l->report(Level::ERROR, new \Exception);
        
        $this->assertEquals(200, $response->getStatus());
    }

    public function testIncludedErrNo(): void
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "included_errno" => E_ERROR | E_WARNING
        ));
        $response = $l->report(
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
        string $dataName,
        array  $result,
        string $scrubField = 'sensitive',
        bool   $recursive = true,
        string $replacement = '*'
    ): void {
    
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
    
    public function scrubDataProvider(): array
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
    public function testScrubGET($testData): void
    {
        $_GET = $testData;
        $result = $this->scrubTestHelper();
        $this->scrubTestAssert('$_GET', $result['data']['request']['GET']);
    }
    
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testGetRequestScrubPOST($testData): void
    {
        $_POST = $testData;
        $result = $this->scrubTestHelper();
        $this->scrubTestAssert('$_POST', $result['data']['request']['POST']);
    }

    /**
     * @dataProvider scrubDataProvider
     */
    public function testGetRequestScrubSession($testData): void
    {
        $_SESSION = $testData;
        $result = $this->scrubTestHelper();
        $this->scrubTestAssert('$_SESSION', $result['data']['request']['session']);
    }
    
    public function testGetScrubbedHeaders(): void
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
    public function testGetRequestScrubExtras($testData): void
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
    public function testMakeDataScrubServerExtras($testData): void
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
    public function testMakeDataScrubCustom($testData): void
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
    public function testMakeDataScrubPerson($testData): void
    {
        $testData['id'] = '123';
        $result = $this->scrubTestHelper(
            array(
                'person' => $testData,
                'scrub_safelist' => array(
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
            "Person recursive.sensitive DID get scrubbed even though it's safelisted."
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testGetRequestScrubBodyContext($testData): void
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
    
    public function scrubQueryStringDataProvider(): array
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
    public function testGetUrlScrub($testData): void
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
    public function testGetRequestScrubQueryString($testData): void
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

    /**
     * @testWith ["emergency"]
     *           ["alert"]
     *           ["critical"]
     *           ["error"]
     *           ["warning"]
     *           ["notice"]
     *           ["info"]
     *           ["debug"]
     */
    public function testPsr3MethodCallsDoNotCrash($method): void
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        // Test that no \Psr\Log\InvalidArgumentException is thrown
        $l->$method("Testing PHP Notifier");
        $this->assertTrue(true);
    }

    /**
     * @dataProvider maxItemsProvider
     */
    public function testMaxItems($maxItemsConfig): void
    {
        $config = array('access_token' => $this->getTestAccessToken());
        if ($maxItemsConfig !== null) {
            $config['max_items'] = $maxItemsConfig;
        }
        
        Rollbar::init($config);
        $logger = Rollbar::logger();
        
        $maxItems = $maxItemsConfig ?? Defaults::get()->maxItems();
        
        for ($i = 0; $i < $maxItems; $i++) {
            $response = $logger->report(Level::INFO, 'testing info level');
            $this->assertEquals(200, $response->getStatus());
        }
      
        $response = $logger->report(Level::INFO, 'testing info level');
        
        $this->assertEquals(0, $response->getStatus());
        $this->assertEquals(
            "Maximum number of items per request has been reached. If you " .
            "want to report more items, please use `max_items` " .
            "configuration option.",
            $response->getInfo()
        );
    }
    
    public function maxItemsProvider(): array
    {
        return array(
            'use default max_items' => array(null),
            'use provided max_items' => array(3)
        );
    }
    
    public function testRaiseOnError(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => 'test',
            "raise_on_error" => true
        ));
        
        $this->expectException(\Exception::class);
        try {
            throw new \Exception();
        } catch (\Exception $ex) {
            $logger->log(Level::ERROR, $ex);
        }
    }
}
