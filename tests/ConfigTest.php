<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\FakeDataBuilder;
use Rollbar\Payload\Body;
use Rollbar\Payload\Data;
use Rollbar\Payload\Level;
use Rollbar\Payload\Message;
use Rollbar\Payload\Payload;
use Rollbar\RollbarLogger;
use Psr\Log\LogLevel;

class ConfigTest extends BaseUnitTestCase
{
    private $error;

    public function setUp()
    {
        $this->error = new ErrorWrapper(E_ERROR, "test", null, null, null);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    private $token = "abcd1234efef5678abcd1234567890be";
    private $env = "rollbar-php-testing";

    public function testAccessToken()
    {
        $config = new Config(array(
            'access_token' => $this->token,
            'environment' => $this->env
        ));
        $this->assertEquals($this->token, $config->getAccessToken());
    }

    public function testAccessTokenFromEnvironment()
    {
        $_ENV['ROLLBAR_ACCESS_TOKEN'] = $this->token;
        $config = new Config(array(
            'environment' => 'testing'
        ));
        $this->assertEquals($this->token, $config->getAccessToken());
    }

    public function testDataBuilder()
    {
        $arr = array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "dataBuilder" => "Rollbar\FakeDataBuilder",
            "dataBuilderOptions" => array("options")
        );
        $config = new Config($arr);
        $this->assertEquals($arr, array_pop(FakeDataBuilder::$args));
    }

    public function testExtend()
    {
        $arr = array(
            "access_token" => $this->token,
            "environment" => $this->env
        );
        $config = new Config($arr);
        $extended = $config->extend(array("one" => 1, "arr" => array()));
        $expected = array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "one" => 1,
            "arr" => array()
        );
        $this->assertEquals($expected, $extended);
    }

    public function testConfigure()
    {
        $arr = array(
            "access_token" => $this->token,
            "environment" => $this->env
        );
        $config = new Config($arr);
        $config->configure(array("one" => 1, "arr" => array()));
        $expected = array(
            "access_token" => $this->token,
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
            "access_token" => $this->token,
            "environment" => $this->env,
            "dataBuilder" => $fdb
        );
        $config = new Config($arr);
        $expected = array(LogLevel::EMERGENCY, "oops", array());
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
            "access_token" => $this->token,
            "environment" => $this->env,
            "transformer" => $transformer
        ));
        $this->assertEquals($pPrime, $config->transform($p, "error", "message", "extra_data"));
    }

    public function testMinimumLevel()
    {
        $c = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "minimumLevel" => "warning"
        ));
        $this->runConfigTest($c);

        $c->configure(array("minimumLevel" => Level::WARNING()));
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
            "access_token" => $this->token,
            "environment" => $this->env,
            "filter" => $filter
        ));
        $this->assertTrue($c->checkIgnored($p, "fake_access_token", $this->error, false));
        $this->assertFalse($c->checkIgnored($p, "fake_access_token", $this->error, false));
    }

    public function testSender()
    {
        $p = m::mock("Rollbar\Payload\Payload");
        $sender = m::mock("Rollbar\Senders\SenderInterface")
            ->shouldReceive("send")
            ->with($p, $this->token)
            ->once()
            ->mock();
        $c = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "sender" => $sender
        ));
        $c->send($p, $this->token);
    }

    public function testEndpoint()
    {
        $payload = m::mock("Rollbar\Payload\Payload");

        $config = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "endpoint" => "http://localhost/api/1/"
        ));

        $this->assertEquals(
            "http://localhost/api/1/item/",
            $config->getSender()->getEndpoint()
        );
    }

    public function testEndpointDefault()
    {
        $payload = m::mock("Rollbar\Payload\Payload");

        $config = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env
        ));

        $this->assertEquals(
            "https://api.rollbar.com/api/1/item/",
            $config->getSender()->getEndpoint()
        );
    }

    public function testBaseApiUrl()
    {
        $payload = m::mock("Rollbar\Payload\Payload");

        $config = new Config(array(
            "access_token" => $this->token,
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
        $payload = m::mock("Rollbar\Payload\Payload");

        $config = new Config(array(
            "access_token" => $this->token,
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
            "access_token" => $this->token,
            "environment" => $this->env,
            "send_message_trace" => true
        ));

        $this->assertTrue($c->getSendMessageTrace());

        $c = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env
        ));

        $this->assertFalse($c->getSendMessageTrace());
    }

    public function testCheckIgnore()
    {
        $called = false;
        $c = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "checkIgnore" => function ($isUncaught, $exc, $payload) use (&$called) {
                $called = true;
            }
        ));
        $data = new Data($this->env, new Body(new Message("test")));
        $data->setLevel(Level::fromName('error'));
        $c->checkIgnored(new Payload($data, $c->getAccessToken()), $this->token, $this->error, false);

        $this->assertTrue($called);
    }

    public function testCheckIgnoreParameters()
    {
        $called = false;
        $isUncaughtPassed = null;
        $errorPassed = null;
        $c = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "checkIgnore" => function ($isUncaught, $exc, $payload) use (&$called, &$isUncaughtPassed, &$errorPassed) {
                $called = true;
                $isUncaughtPassed = $isUncaught;
                $errorPassed = $exc;
            }
        ));
        $data = new Data($this->env, new Body(new Message("test")));
        $data->setLevel(Level::fromName('error'));
        $c->checkIgnored(new Payload($data, $c->getAccessToken()), $this->token, $this->error, true);

        $this->assertTrue($called);
        $this->assertTrue($isUncaughtPassed);
        $this->assertEquals($this->error, $errorPassed);
    }

    public function testCaptureErrorStacktraces()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "capture_error_stacktraces" => false
        ));

        $dataBuilder = $logger->getDataBuilder();

        $result = $dataBuilder->makeData(
            Level::fromName('error'),
            new \Exception(),
            array()
        );

        $this->assertEmpty($result->getBody()->getValue()->getFrames());
    }

    /**
     * @dataProvider useErrorReportingProvider
     */
    public function testUseErrorReporting($setup, $expected)
    {
        extract($setup);
        $called = false;
        $c = new Config(array(
            "access_token" => $this->token,
            "environment" => $this->env,
            "checkIgnore" => function ($isUncaught, $exc, $payload) use (&$called) {
                $called = true;
            },
            "use_error_reporting" => $use_error_reporting
        ));

        $data = new Data($this->env, new Body(new Message("test")));
        $data->setLevel(Level::fromName('error'));

        if ($error_reporting !== null) {
            $errorReportingTemp = error_reporting();
            error_reporting($error_reporting);
        }

        $result = $c->checkIgnored(new Payload($data, $c->getAccessToken()), $this->token, $this->error, false);
        $this->assertEquals($expected, $result);

        if ($error_reporting) {
            error_reporting($errorReportingTemp);
        }
    }

    public function useErrorReportingProvider()
    {
        return array(
            "use_error_reporting off" => array(
                array(
                    "use_error_reporting" => false,
                    "error_reporting" => null
                ),
                false
            ),
            "use_error_reporting on & errno not covered" => array(
                array(
                    "use_error_reporting" => true,
                    "error_reporting" => E_WARNING
                ),
                true
            ),
            "use_error_reporting on & errno covered" => array(
                array(
                    "use_error_reporting" => true,
                    "error_reporting" => E_ERROR
                ),
                false
            )
        );
    }
}
