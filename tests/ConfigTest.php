<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\FakeDataBuilder;
use Rollbar\Payload\Body;
use Rollbar\Payload\Data;
use Rollbar\Payload\Level;
use Rollbar\Payload\Message;
use Rollbar\Payload\Payload;
use Rollbar\RollbarLogger;
use Rollbar\Defaults;

use Rollbar\TestHelpers\Exceptions\SilentExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\FiftyFiftyExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\FiftyFityChildExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\QuarterExceptionSampleRate;
use Rollbar\TestHelpers\Exceptions\VerboseExceptionSampleRate;

class ConfigTest extends BaseRollbarTest
{
    private $error;

    public function setUp()
    {
        $this->error = new ErrorWrapper(
            E_ERROR,
            "test",
            null,
            null,
            null,
            new Utilities
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }
    
    private $env = "rollbar-php-testing";

    public function testAccessToken()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertEquals($this->getTestAccessToken(), $config->getAccessToken());
    }

    public function testEnabled()
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

    public function testTransmit()
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

    public function testLogPayload()
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

    public function testLoggingPayload()
    {
        $logPayloadLoggerMock = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
        $logPayloadLoggerMock->expects($this->once())
                        ->method('debug')
                        ->with(
                            $this->matchesRegularExpression(
                                '/Sending payload with .*:\n\{"data":/'
                            )
                        );
        $senderMock = $this->getMockBuilder('\Rollbar\Senders\SenderInterface')
                        ->getMock();
        $senderMock->method('send')->willReturn(true);

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

    public function testConfigureLogPayloadLogger()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertInstanceOf('\Monolog\Logger', $config->logPayloadLogger());
        $handlers = $config->logPayloadLogger()->getHandlers();
        $handler = $handlers[0];
        $this->assertInstanceOf('\Monolog\Handler\ErrorLogHandler', $handler);
        $this->assertEquals(\Monolog\Logger::DEBUG, $handler->getLevel());

        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'log_payload_logger' => new \Psr\Log\NullLogger()
        ));
        $this->assertInstanceOf('\Psr\Log\NullLogger', $config->logPayloadLogger());
    }

    public function testVerbose()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        // assert the appropriate default logger
        $this->assertEquals(Config::VERBOSE_NONE, $config->verbose());
        $this->assertInstanceOf('\Monolog\Logger', $config->verboseLogger());
        // assert the appropriate default handler
        $handlers = $config->verboseLogger()->getHandlers();
        $handler = $handlers[0];
        $this->assertInstanceOf('\Monolog\Handler\ErrorLogHandler', $handler);
        // assert the appropriate default handler level
        $this->assertEquals($config->verboseInteger(), $handler->getLevel());
        
        // assert the verbosity level in the handler matches the level in the config
        $config->configure(array('verbose' => \Psr\Log\LogLevel::DEBUG));
        $handlers = $config->verboseLogger()->getHandlers();
        $handler = $handlers[0];
        $this->assertEquals($config->verboseInteger(), $handler->getLevel());
    }

    public function testVerboseInfo()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'verbose' => \Psr\Log\LogLevel::INFO
        ));

        $handlerMock = $this->getMockBuilder('\Monolog\Handler\ErrorLogHandler')
            ->setMethods(array('handle'))
            ->getMock();

        $handlerMock->expects($this->once())->method('handle');

        $config->verboseLogger()->setHandlers(array($handlerMock));

        $this->assertTrue($config->verboseLogger()->info('Test trace'));
    }

    public function testVerboseInteger()
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

    public function testConfigureVerboseLogger()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        $this->assertInstanceOf('\Monolog\Logger', $config->verboseLogger());
        
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'verbose_logger' => new \Psr\Log\NullLogger()
        ));
        $this->assertInstanceOf('\Psr\Log\NullLogger', $config->verboseLogger());
    }

    public function testAccessTokenFromEnvironment()
    {
        $_ENV['ROLLBAR_ACCESS_TOKEN'] = $this->getTestAccessToken();
        $config = new Config(array(
            'environment' => 'testing'
        ));
        $this->assertEquals($this->getTestAccessToken(), $config->getAccessToken());
    }

    public function testDataBuilder()
    {
        $arr = array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env
        );
        $config = new Config($arr);
        $this->assertInstanceOf('Rollbar\DataBuilder', $config->getDataBuilder());
    }

    public function testExtend()
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

    public function testConfigure()
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

    public function testExplicitDataBuilder()
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

    public function testTransformer()
    {
        $p = m::mock("Rollbar\Payload\Payload");
        $pPrime = m::mock("Rollbar\Payload\Payload");
        $transformer = m::mock("Rollbar\TransformerInterface");
        $transformer->shouldReceive('transform')
            ->once()
            ->with($p, "error", "message", "extra_data")
            ->andReturn($pPrime)
            ->mock();
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "transformer" => $transformer
        ));
        $this->assertEquals($pPrime, $config->transform($p, "error", "message", "extra_data"));
    }

    public function testMinimumLevel()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env
        ));
        
        $this->assertPayloadNotIgnored($config, $this->prepareMockPayload(Level::DEBUG()));
        
        $config->configure(array('minimum_level' => Level::WARNING()));
        
        $this->assertPayloadIgnored($config, $this->prepareMockPayload(Level::DEBUG()));
        $this->assertPayloadNotIgnored($config, $this->prepareMockPayload(Level::WARNING()));
        
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => $this->env,
            'minimumLevel' => Level::ERROR()
        ));
        
        $this->assertPayloadIgnored($config, $this->prepareMockPayload(Level::WARNING()));
        $this->assertPayloadNotIgnored($config, $this->prepareMockPayload(Level::ERROR()));
    }
    
    public function assertPayloadIgnored($config, $payload)
    {
        $this->assertTrue($config->checkIgnored($payload, null, $this->error, false));
    }
    
    public function assertPayloadNotIgnored($config, $payload)
    {
        $this->assertFalse($config->checkIgnored($payload, null, $this->error, false));
    }
    
    private function prepareMockPayload($level)
    {
        $data = m::mock("Rollbar\Payload\Data")
            ->shouldReceive('getLevel')
            ->andReturn($level)
            ->mock();
        return new Payload($data, $this->getTestAccessToken());
    }

    public function testReportSuppressed()
    {
        $this->assertTrue(true, "Don't know how to unit test this. PRs welcome");
    }

    public function testFilter()
    {
        $d = m::mock("Rollbar\Payload\Data")
            ->shouldReceive("getLevel")
            ->andReturn(Level::CRITICAL())
            ->mock();
        $p = m::mock("Rollbar\Payload\Payload")
            ->shouldReceive("getData")
            ->andReturn($d)
            ->mock();
        $filter = m::mock("Rollbar\FilterInterface")
            ->shouldReceive("shouldSend")
            ->twice()
            ->andReturn(true, false)
            ->mock();
        $c = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "filter" => $filter
        ));
        $this->assertTrue($c->checkIgnored($p, "fake_access_token", $this->error, false));
        $this->assertFalse($c->checkIgnored($p, "fake_access_token", $this->error, false));
    }

    public function testSender()
    {
        $p = m::mock("Rollbar\Payload\EncodedPayload");
        $sender = m::mock("Rollbar\Senders\SenderInterface")
            ->shouldReceive("send")
            ->with($p, $this->getTestAccessToken())
            ->once()
            ->mock();
        $c = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "sender" => $sender
        ));
        $c->send($p, $this->getTestAccessToken());
    }
    
    public function testEndpoint()
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

    public function testCustom()
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "custom" => array(
                "foo" => "bar",
                "fuzz" => "buzz"
            )
        ));
        
        $dataBuilder = $config->getDataBuilder();
        
        $result = $dataBuilder->makeData(
            Level::INFO,
            "Test message with custom data added dynamically.",
            array()
        );
        
        $custom = $result->getCustom();
        
        $this->assertEquals("bar", $custom["foo"]);
        $this->assertEquals("buzz", $custom["fuzz"]);
    }
    
    public function testMaxItems()
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
    
    public function testCustomDataMethod()
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

    public function testEndpointDefault()
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
    
    public function testBaseApiUrl()
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
    
    public function testBaseApiUrlDefault()
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
    
    public function testRaiseOnError()
    {
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "raise_on_error" => true
        ));
        
        $this->assertTrue($config->getRaiseOnError());
    }

    public function testSendMessageTrace()
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

    public function testCheckIgnore()
    {
        $called = false;
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "check_ignore" => function () use (&$called) {
                $called = true;
            }
        ));
        $levelFactory = $config->getLevelFactory();
        
        $data = new Data($this->env, new Body(new Message("test")));
        $data->setLevel($levelFactory->fromName(Level::ERROR));
        
        $config->checkIgnored(
            new Payload(
                $data,
                $config->getAccessToken()
            ),
            $this->getTestAccessToken(),
            $this->error,
            false
        );

        $this->assertTrue($called);
    }

    public function testCheckIgnoreParameters()
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
        
        $levelFactory = $config->getLevelFactory();
        
        $data = new Data($this->env, new Body(new Message("test")));
        $data->setLevel($levelFactory->fromName(Level::ERROR));
        
        $config->checkIgnored(
            new Payload(
                $data,
                $config->getAccessToken()
            ),
            $this->getTestAccessToken(),
            $this->error,
            true
        );

        $this->assertTrue($called);
        $this->assertTrue($isUncaughtPassed);
        $this->assertEquals($this->error, $errorPassed);
    }
    
    public function testCaptureErrorStacktraces()
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
    public function testUseErrorReporting($use_error_reporting, $error_reporting, $expected)
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
    
    public function useErrorReportingProvider()
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
    public function testExceptionSampleRate($exception, $expected)
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
    
    public function providerExceptionSampleRate()
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
