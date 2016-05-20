<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\FakeDataBuilder;
use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use Psr\Log\LogLevel;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    private $token = "abcd1234efef5678abcd1234567890be";
    private $env = "rollbar-php-testing";

    public function testAccessToken()
    {
        $config = new Config(array(
            'accessToken' => $this->token,
            'environment' => $this->env
        ));
        $this->assertEquals($this->token, $config->getAccessToken());
    }

    public function testDataBuilder()
    {
        $arr = array(
            "accessToken" => $this->token,
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
            "accessToken" => $this->token,
            "environment" => $this->env
        );
        $config = new Config($arr);
        $extended = $config->extend(array("one" => 1, "arr" => array()));
        $expected = array(
            "accessToken" => $this->token,
            "environment" => $this->env,
            "one" => 1,
            "arr" => array()
        );
        $this->assertEquals($expected, $extended);
    }

    public function testConfigure()
    {
        $arr = array(
            "accessToken" => $this->token,
            "environment" => $this->env
        );
        $config = new Config($arr);
        $config->configure(array("one" => 1, "arr" => array()));
        $expected = array(
            "accessToken" => $this->token,
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
            "accessToken" => $this->token,
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
            "accessToken" => $this->token,
            "environment" => $this->env,
            "transformer" => $transformer
        ));
        $this->assertEquals($pPrime, $config->transform($p, "error", "message", "extra_data"));
    }

    public function testMinimumLevel()
    {
        $testConfig = function ($config) {
            $debugData = m::mock("Rollbar\Payload\Data")
                ->shouldReceive('getLevel')
                ->andReturn(Level::DEBUG())
                ->mock();
            $debug = new Payload($debugData, $this->token);
            $this->assertTrue($config->checkIgnored($debug, null));

            $criticalData = m::mock("Rollbar\Payload\Data")
                ->shouldReceive('getLevel')
                ->andReturn(Level::CRITICAL())
                ->mock();
            $critical = new Payload($criticalData, $this->token);
            $this->assertFalse($config->checkIgnored($critical, null));

            $warningData = m::mock("Rollbar\Payload\Data")
                ->shouldReceive('getLevel')
                ->andReturn(Level::warning())
                ->mock();
            $warning = new Payload($warningData, $this->token);
            $this->assertFalse($config->checkIgnored($warning, null));
        };

        $c = new Config(array(
            "accessToken" => $this->token,
            "environment" => $this->env,
            "minimumLevel" => "warning"
        ));
        $testConfig($c);

        $c->configure(array("minimumLevel" => Level::WARNING()));
        $testConfig($c);

        $c->configure(array("minimumLevel" => Level::WARNING()->toInt()));
        $testConfig($c);
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
            "accessToken" => $this->token,
            "environment" => $this->env,
            "filter" => $filter
        ));
        $this->assertTrue($c->checkIgnored($p, "fake_access_token"));
        $this->assertFalse($c->checkIgnored($p, "fake_access_token"));
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
            "accessToken" => $this->token,
            "environment" => $this->env,
            "sender" => $sender
        ));
        $c->send($p, $this->token);
    }

    public function testHandleResponse()
    {

    }
}
