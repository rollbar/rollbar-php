<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\FakeDataBuilder;
use Rollbar\Payload\Body;
use Rollbar\Payload\Data;
use Rollbar\Payload\Level;
use Rollbar\Payload\Message;
use Rollbar\Payload\Payload;
use Rollbar\RollbarLogger;

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
        $c = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "minimumLevel" => "warning"
        ));
        $this->runConfigTest($c);

        $c->configure(array("minimumLevel" => Level::WARNING));
        $this->runConfigTest($c);
        
        $c->configure(array("minimumLevel" => Level::WARNING()->toInt()));
        $this->runConfigTest($c);
    }

    private function runConfigTest($config)
    {
        $accessToken = $config->getAccessToken();
        $debugData = m::mock("Rollbar\Payload\Data")
            ->shouldReceive('getLevel')
            ->andReturn(Level::DEBUG())
            ->mock();
        $debug = new Payload($debugData, $accessToken);
        $this->assertTrue($config->checkIgnored($debug, null, $this->error, false));

        $criticalData = m::mock("Rollbar\Payload\Data")
            ->shouldReceive('getLevel')
            ->andReturn(Level::CRITICAL())
            ->mock();
        $critical = new Payload($criticalData, $accessToken);
        $this->assertFalse($config->checkIgnored($critical, null, $this->error, false));

        $warningData = m::mock("Rollbar\Payload\Data")
            ->shouldReceive('getLevel')
            ->andReturn(Level::warning())
            ->mock();
        $warning = new Payload($warningData, $accessToken);
        $this->assertFalse($config->checkIgnored($warning, null, $this->error, false));
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
    
    public function testVerbosity()
    {
        $expected = 3;
        
        $config = new Config(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => $this->env,
            "verbosity" => $expected
        ));
        
        $this->assertEquals($expected, $config->getVerbosity());
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
