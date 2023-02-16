<?php namespace Rollbar;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Monolog\Handler\NoopHandler;
use Rollbar\FakeDataBuilder;
use Rollbar\Payload\Body;
use Rollbar\Payload\Data;
use Rollbar\Payload\Level;
use Rollbar\Payload\Message;
use Rollbar\Payload\Payload;
use Rollbar\RollbarLogger;
use Rollbar\Defaults;

use Rollbar\TestHelpers\CustomSerializable;
use Rollbar\TestHelpers\DeprecatedSerializable;
use Rollbar\TestHelpers\Exceptions\SilentExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\FiftyFiftyExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\FiftyFityChildExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\QuarterExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\VerboseExceptionSampleRate;
use Rollbar\Senders\SenderInterface;
use Rollbar\Payload\EncodedPayload;
use Rollbar\FilterInterface;
use Rollbar\TransformerInterface;
use Rollbar\DataBuilder;
use Psr\Log\NullLogger;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Psr\Log\LoggerInterface;

class ConfigTest extends BaseRollbarTest
{
    use MockeryPHPUnitIntegration;

    private ErrorWrapper $error;

    public function setUp(): void
    {
        $this->error = new ErrorWrapper(
            E_ERROR,
            "test",
            null,
            null,
            null,
            new Utilities
        );
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }
    
    private string $env = "rollbar-php-testing";

    public function testAccessToken(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertEquals($this->getTestAccessToken(), $config->getAccessToken());
    }

    public function testEnabled(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertTrue($config->enabled());
        
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'enabled' => false
        ));
        $this->assertFalse($config->enabled());
    }

    public function testTransmit(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertTrue($config->transmitting());
        
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'transmit' => false
        ));
        $this->assertFalse($config->transmitting());
    }

    public function testLogPayload(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertFalse($config->loggingPayload());
        
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'log_payload' => true
        ));
        $this->assertTrue($config->loggingPayload());
    }

    public function testLoggingPayload(): void
    {
        $logPayloadLoggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logPayloadLoggerMock->expects($this->once())
                        ->method('debug')
                        ->with(
                            $this->matchesRegularExpression(
                                '/Sending payload with .*:\n\{"data":/'
                            )
                        );
        $senderMock = $this->getMockBuilder(SenderInterface::class)
                        ->getMock();
        $senderMock->method('send')->willReturn(new Response(200, 'Test'));

        $payload = new \Rollbar\Payload\EncodedPayload(array('data'=>array()));
        $payload->encode();

        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "log_payload" => true,
            "log_payload_logger" => $logPayloadLoggerMock,
            "sender" => $senderMock
        ));
        $config->send($payload, $this->getTestAccessToken());
    }

    public function testConfigureLogPayloadLogger(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertInstanceOf(Logger::class, $config->logPayloadLogger());
        $handlers = $config->logPayloadLogger()->getHandlers();
        $handler = $handlers[0];
        $this->assertInstanceOf(ErrorLogHandler::class, $handler);

        // The Level class was created in Monolog v3.0.0. This is needed to support both v2 and v3.
        if (class_exists('\Monolog\Level')) {
            $this->assertEquals(\Monolog\Level::Debug, $handler->getLevel());
        } else {
            $this->assertEquals(Logger::DEBUG, $handler->getLevel());
        }

        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'log_payload_logger' => new \Psr\Log\NullLogger()
        ));
        $this->assertInstanceOf(NullLogger::class, $config->logPayloadLogger());
    }

    public function testVerbose(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        // assert the appropriate default logger
        $this->assertEquals(Config::VERBOSE_NONE, $config->verbose());
        $this->assertInstanceOf(Logger::class, $config->verboseLogger());
        // assert the appropriate default handler
        $handlers = $config->verboseLogger()->getHandlers();
        $handler = $handlers[0];
        $this->assertInstanceOf(NoopHandler::class, $handler);
        
        // assert the verbosity level in the handler matches the level in the config
        $config->configure(array('verbose' => \Psr\Log\LogLevel::DEBUG));
        $handlers = $config->verboseLogger()->getHandlers();
        $handler = $handlers[0];

        // The Level class was created in Monolog v3.0.0. This is needed to support both v2 and v3.
        if (class_exists('\Monolog\Level')) {
            $this->assertEquals($config->verboseInteger(), $handler->getLevel()->value);
        } else {
            $this->assertEquals($config->verboseInteger(), $handler->getLevel());
        }
    }

    public function testVerboseInfo(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'verbose' => \Psr\Log\LogLevel::INFO
        ));

        $handlerMock = $this->getMockBuilder(ErrorLogHandler::class)
            ->setMethods(array('handle'))
            ->getMock();

        $handlerMock->expects($this->once())->method('handle');

        $config->verboseLogger()->setHandlers(array($handlerMock));

        $config->verboseLogger()->info('Test trace');
    }

    public function testVerboseInteger(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'verbose' => Config::VERBOSE_NONE
        ));
        $this->assertEquals(1000, $config->verboseInteger());

        $config->configure(array('verbose' => \Psr\Log\LogLevel::DEBUG));
        $this->assertEquals(100, $config->verboseInteger());
    }

    public function testConfigureVerboseLogger(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertInstanceOf(Logger::class, $config->verboseLogger());
        
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'verbose_logger' => new \Psr\Log\NullLogger()
        ));
        $this->assertInstanceOf(NullLogger::class, $config->verboseLogger());
    }

    public function testAccessTokenFromEnvironment(): void
    {
        $_ENV['ROLLBAR_ACCESS_TOKEN'] = $this->getTestAccessToken();
        $config = new Config(array(
            'environment' => 'testing'
        ));
        $this->assertEquals($this->getTestAccessToken(), $config->getAccessToken());
    }

    public function testDataBuilder(): void
    {
        $arr = array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env
        );
        $config = new Config($arr);
        $this->assertInstanceOf(DataBuilder::class, $config->getDataBuilder());
    }

    public function testExtend(): void
    {
        $arr = array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env
        );
        $config = new Config($arr);
        $extended = $config->extend(array("one" => 1, "arr" => array()));
        $expected = array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "one" => 1,
            "arr" => array()
        );
        $this->assertEquals($expected, $extended);
    }

    public function testConfigure(): void
    {
        $arr = array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env
        );
        $config = new Config($arr);
        $config->configure(array("one" => 1, "arr" => array()));
        $expected = array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "one" => 1,
            "arr" => array()
        );
        $this->assertEquals($expected, $config->getConfigArray());
    }

    public function testExplicitDataBuilder(): void
    {
        $fdb = new FakeDataBuilder(array());
        $arr = array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "dataBuilder" => $fdb
        );
        $config = new Config($arr);
        $expected = array(Level::EMERGENCY, "oops", array());
        $config->getRollbarData($expected[0], $expected[1], $expected[2]);
        $this->assertEquals($expected, array_pop(FakeDataBuilder::$logged));
    }

    public function testTransformer(): void
    {
        $p = m::mock(Payload::class);
        $pPrime = m::mock(Payload::class);
        $transformer = m::mock(TransformerInterface::class);
        $transformer->shouldReceive('transform')
            ->once()
            ->with($p, "error", "message", [ "extra_data" ])
            ->andReturn($pPrime)
            ->mock();
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "transformer" => $transformer
        ));
        $this->assertEquals($pPrime, $config->transform($p, "error", "message", [ "extra_data" ]));
    }

    public function testMinimumLevel(): void
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        
        $this->assertPayloadNotIgnored($config, $this->prepareMockPayload(LevelFactory::fromName(Level::DEBUG)));
        
        $config->configure(array('minimum_level' => LevelFactory::fromName(Level::WARNING)));
        
        $this->assertPayloadIgnored($config, $this->prepareMockPayload(LevelFactory::fromName(Level::DEBUG)));
        $this->assertPayloadNotIgnored($config, $this->prepareMockPayload(LevelFactory::fromName(Level::WARNING)));
        
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'minimumLevel' => Level::ERROR
        ));
        
        $this->assertPayloadIgnored($config, $this->prepareMockPayload(LevelFactory::fromName(Level::WARNING)));
        $this->assertPayloadNotIgnored($config, $this->prepareMockPayload(LevelFactory::fromName(Level::ERROR)));
    }
    
    public function assertPayloadIgnored($config, $payload): void
    {
        $this->assertTrue($config->checkIgnored($payload, $this->error, false));
    }
    
    public function assertPayloadNotIgnored($config, $payload): void
    {
        $this->assertFalse($config->checkIgnored($payload, $this->error, false));
    }
    
    private function prepareMockPayload($level): Payload
    {
        $data = m::mock(Data::class)
            ->shouldReceive('getLevel')
            ->andReturn($level)
            ->mock();
        return new Payload($data, $this->getTestAccessToken());
    }

    /**
     * Test the shouldSuppress method, which is driven by the configuration
     * given and the value of the PHP engine's error_reporting() setting.
     *
     *            - error reporting value
     *            |  - configuration key
     *            |  |                   - configuration value
     *            |  |                   |      - shouldSuppress() result
     *            v  v                   v      v
     * @testWith [0, "reportSuppressed", false, true]
     *           [0, "reportSuppressed", true,  false]
     *           [1, "reportSuppressed", false, false]
     *           [0, "report_suppressed", false, true]
     *           [0, "report_suppressed", true,  false]
     *           [1, "report_suppressed", false, false]
     */
    public function testReportSuppressed($errorReporting, $configKey, $configValue, $shouldSuppressExpect): void
    {
        $oldErrorReporting = error_reporting($errorReporting);
        try {
            $config = new Config(array(
                $configKey => $configValue
            ));
            $this->assertSame(
                $shouldSuppressExpect,
                $config->shouldSuppress(),
                'shouldSuppress() did not return the expected value for the error_reporting and configuration given'
            );
        } finally {
            error_reporting($oldErrorReporting);
        }
    }

    public function testReportSuppressedActuallySuppressed()
    {
        $config = new Config(array(
            'report_suppressed' => false,
            "access_token" => $this->getTestAccessToken()
        ));
        $this->assertFalse($config->shouldSuppress());
        $this->assertTrue(@$config->shouldSuppress());
    }

    public function testFilter(): void
    {
        $d = m::mock(Data::class)
            ->shouldReceive("getLevel")
            ->andReturn(LevelFactory::fromName(Level::CRITICAL))
            ->mock();
        $p = m::mock(Payload::class)
            ->shouldReceive("getData")
            ->andReturn($d)
            ->mock();
        $filter = m::mock(FilterInterface::class)
            ->shouldReceive("shouldSend")
            ->twice()
            ->andReturn(true, false)
            ->mock();
        $c = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "filter" => $filter
        ));
        $this->assertTrue($c->checkIgnored($p, $this->error, false));
        $this->assertFalse($c->checkIgnored($p, $this->error, false));
    }

    public function testSender(): void
    {
        $p = m::mock(EncodedPayload::class);
        $sender = m::mock(SenderInterface::class);
        $sender->shouldReceive("send")
            ->with($p, $this->getTestAccessToken())
            ->once()
            ->mock();
        $sender->shouldReceive('requireAccessToken')
            ->once()
            ->mock();
        $c = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "sender" => $sender
        ));
        $c->send($p, $this->getTestAccessToken());
    }
    
    public function testEndpoint(): void
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "endpoint" => "http://localhost/api/1/"
        ));
        
        $this->assertEquals(
            "http://localhost/api/1/item/",
            $config->getSender()->getEndpoint()
        );
    }

    public function testCustom(): void
    {
        $expectedCustom = array(
            "foo" => "bar",
            "fuzz" => "buzz"
        );
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "custom" => $expectedCustom,
        ));

        $result = $config->getDataBuilder()->makeData(
            Level::INFO,
            "Test message with custom data added dynamically.",
            array(),
        );
        
        $actualCustom = $result->getCustom();
        
        $this->assertSame($expectedCustom, $actualCustom);
    }

    public function testMaxItems(): void
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken()
        ));
        
        $this->assertEquals(Defaults::get()->maxItems(), $config->getMaxItems());
        
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "max_items" => Defaults::get()->maxItems()+1
        ));
        
        $this->assertEquals(Defaults::get()->maxItems()+1, $config->getMaxItems());
    }
    
    public function testCustomDataMethod(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "custom_data_method" => function ($toLog, $customDataMethodContext) {
                
                return array(
                    'data_from_my_custom_method' => $customDataMethodContext['foo']
                );
            }
        ));
        
        $dataBuilder = $logger->getDataBuilder();
        
        $result = $dataBuilder->makeData(
            Level::ERROR,
            new \Exception(),
            array(
                'custom_data_method_context' => array('foo' => 'bar')
            )
        )->getCustom();
        
        $this->assertEquals('bar', $result['data_from_my_custom_method']);
    }

    public function testEndpointDefault(): void
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env
        ));
        
        $this->assertEquals(
            "https://api.rollbar.com/api/1/item/",
            $config->getSender()->getEndpoint()
        );
    }
    
    public function testBaseApiUrl(): void
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "base_api_url" => "http://localhost/api/1/"
        ));
        
        $this->assertEquals(
            "http://localhost/api/1/item/",
            $config->getSender()->getEndpoint()
        );
    }
    
    public function testBaseApiUrlDefault(): void
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env
        ));
        
        $this->assertEquals(
            "https://api.rollbar.com/api/1/item/",
            $config->getSender()->getEndpoint()
        );
    }
    
    public function testRaiseOnError(): void
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "raise_on_error" => true
        ));
        
        $this->assertTrue($config->getRaiseOnError());
    }

    public function testSendMessageTrace(): void
    {
        $c = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "send_message_trace" => true
        ));
        
        $this->assertTrue($c->getSendMessageTrace());
        
        $c = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env
        ));
        
        $this->assertFalse($c->getSendMessageTrace());
    }

    public function testCheckIgnore(): void
    {
        $called = false;
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "check_ignore" => function () use (&$called) {
                $called = true;
            }
        ));

        $data = new Data($this->env, new Body(new Message("test")));
        $data->setLevel(LevelFactory::fromName(Level::ERROR));
        
        $config->checkIgnored(
            new Payload(
                $data,
                $config->getAccessToken()
            ),
            $this->error,
            false
        );

        $this->assertTrue($called);
    }

    public function testCheckIgnoreParameters(): void
    {
        $called = false;
        $isUncaughtPassed = null;
        $errorPassed = null;
        
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "check_ignore" => function (
                $isUncaught,
                $exc
            ) use (
                &$called,
                &$isUncaughtPassed,
                &$errorPassed
) {
                $called = true;
                $isUncaughtPassed = $isUncaught;
                $errorPassed = $exc;
            }
        ));
        
        $data = new Data($this->env, new Body(new Message("test")));
        $data->setLevel(LevelFactory::fromName(Level::ERROR));
        
        $config->checkIgnored(
            new Payload(
                $data,
                $config->getAccessToken()
            ),
            $this->error,
            true
        );

        $this->assertTrue($called);
        $this->assertTrue($isUncaughtPassed);
        $this->assertEquals($this->error, $errorPassed);
    }
    
    public function testCaptureErrorStacktraces(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "capture_error_stacktraces" => false
        ));
        
        $dataBuilder = $logger->getDataBuilder();
        
        $result = $dataBuilder->makeData(
            Level::ERROR,
            new \Exception(),
            array()
        );
        
        $this->assertEmpty($result->getBody()->getValue()->getFrames());
    }

    /**
     * @dataProvider useErrorReportingProvider
     */
    public function testUseErrorReporting($use_error_reporting, $error_reporting, $expected): void
    {
        $called = false;
        
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "check_ignore" => function () use (&$called) {
                $called = true;
            },
            "use_error_reporting" => $use_error_reporting
        ));
        
        if ($error_reporting !== null) {
            $errorReportingTemp = error_reporting();
            error_reporting($error_reporting);
        }
        
        $result = $config->internalCheckIgnored(
            Level::ERROR,
            $this->error
        );
        
        $this->assertEquals($expected, $result);
        
        if ($error_reporting) {
            error_reporting($errorReportingTemp);
        }
    }
    
    public function useErrorReportingProvider(): array
    {
        return array(
            "use_error_reporting off" => array(
                false, // "use_error_reporting"
                null,  // "error_reporting"
                false
            ),
            "use_error_reporting on & errno not covered" => array(
                true,      // "use_error_reporting"
                E_WARNING, // "error_reporting"
                true
            ),
            "use_error_reporting on & errno covered" => array(
                true,    // "use_error_reporting"
                E_ERROR, // "error_reporting"
                false
            )
        );
    }
    
    /**
     * @dataProvider providerExceptionSampleRate
     */
    public function testExceptionSampleRate($exception, $expected): void
    {
        $config = new Config(array(
            "access_token" => "ad865e76e7fb496fab096ac07b1dbabb",
            "environment" => "testing-php",
            "exception_sample_rates" => array(
                get_class($exception) => $expected
            )
        ));
        
        $sampleRate = $config->exceptionSampleRate($exception);
        
        $this->assertEquals($expected, $sampleRate);
    }
    
    public function providerExceptionSampleRate(): array
    {
        return array(
            array(
                new \Exception,
                1.0
            ),
            array(
                new SilentExceptionSampleRate,
                0.0
            ),
            array(
                new FiftyFiftyExceptionSampleRate,
                0.5
            ),
            array(
                new FiftyFityChildExceptionSampleRate,
                0.5
            ),
            array(
                new QuarterExceptionSampleRate,
                0.25
            ),
            array(
                new VerboseExceptionSampleRate,
                1.0
            ),
        );
    }
}
