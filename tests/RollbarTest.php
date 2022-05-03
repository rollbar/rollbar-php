<?php namespace Rollbar;

use Rollbar\Rollbar;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;
use Rollbar\RollbarLogger;

/**
 * Usage of static method Rollbar::logger() is intended here.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RollbarTest extends BaseRollbarTest
{
    
    public function setUp(): void
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['environment'] = 'test';
    }

    private static string|array $simpleConfig = array();
    
    public static function setUpBeforeClass(): void
    {
        Rollbar::destroy();
    }
    
    public function testInitWithConfig(): void
    {
        Rollbar::init(self::$simpleConfig);
        
        $this->assertInstanceOf(RollbarLogger::class, Rollbar::logger());
        $this->assertEquals(new Config(self::$simpleConfig), Rollbar::logger()->getConfig());
    }
    
    public function testInitWithLogger(): void
    {
        $logger = $this->getMockBuilder(RollbarLogger::class)->disableOriginalConstructor()->getMock();

        Rollbar::init($logger);
        
        $this->assertSame($logger, Rollbar::logger());
    }
    
    public function testInitConfigureLogger(): void
    {
        $logger = $this->getMockBuilder(RollbarLogger::class)->disableOriginalConstructor()->getMock();
        $logger->expects($this->once())->method('configure')->with(self::$simpleConfig);

        Rollbar::init($logger);
        Rollbar::init(self::$simpleConfig);
    }
    
    public function testInitReplaceLogger(): void
    {
        Rollbar::init(self::$simpleConfig);

        $this->assertInstanceOf(RollbarLogger::class, Rollbar::logger());

        $logger = $this->getMockBuilder(RollbarLogger::class)->disableOriginalConstructor()->getMock();

        Rollbar::init($logger);

        $this->assertSame($logger, Rollbar::logger());
    }

    public function testLogException(): void
    {
        Rollbar::init(self::$simpleConfig);

        try {
            throw new \Exception('test exception');
        } catch (\Exception $e) {
            Rollbar::log(Level::ERROR, $e);
        }
        
        $this->assertTrue(true);
    }
    
    public function testLogMessage(): void
    {
        Rollbar::init(self::$simpleConfig);
      
        $response = Rollbar::log(Level::INFO, 'testing info level');
      
        $this->assertTrue(true);
    }
    
    public function testLogExtraData(): void
    {
        Rollbar::init(self::$simpleConfig);
        
        $logger = Rollbar::logger();
        $reflection = new \ReflectionClass(get_class($logger));
        $method = $reflection->getMethod('getPayload');
        $method->setAccessible(true);
        
        $payload = $method->invokeArgs(
            $logger,
            array(
                self::$simpleConfig['access_token'],
                Level::INFO,
                'testing extra data',
                array("some_key" => "some value") // key-value additional data
            )
        );
        
        $extra = $payload->getData()->getBody()->getExtra();
        
        $this->assertEquals(
            "some value",
            $extra['some_key']
        );
    }
    
    public function testDebug(): void
    {
        $this->shortcutMethodTestHelper(Level::DEBUG);
    }
    
    public function testInfo(): void
    {
        $this->shortcutMethodTestHelper(Level::INFO);
    }
    
    public function testNotice(): void
    {
        $this->shortcutMethodTestHelper(Level::NOTICE);
    }
    
    public function testWarning(): void
    {
        $this->shortcutMethodTestHelper(Level::WARNING);
    }
    
    public function testError(): void
    {
        $this->shortcutMethodTestHelper(Level::ERROR);
    }
    
    public function testCritical(): void
    {
        $this->shortcutMethodTestHelper(Level::CRITICAL);
    }
    
    public function testAlert(): void
    {
        $this->shortcutMethodTestHelper(Level::ALERT);
    }
    
    public function testEmergency(): void
    {
        $this->shortcutMethodTestHelper(Level::EMERGENCY);
    }
    
    protected function shortcutMethodTestHelper($level): void
    {
        $message = "shortcutMethodTestHelper: $level";
        
        $result = Rollbar::$level($message);
        $expected = Rollbar::log($level, $message);
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Below are backwards compatibility tests with v0.18.2
     */
    public function testBackwardsSimpleMessageVer(): void
    {
        Rollbar::init(self::$simpleConfig);

        $uuid = Rollbar::report_message("Hello world");
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }
    
    public function testBackwardsSimpleError(): void
    {
        set_error_handler(function () {
        }); // disable PHPUnit's error handler
        
        Rollbar::init(self::$simpleConfig);
        
        $result = Rollbar::report_php_error(E_ERROR, "Runtime error", "the_file.php", 1);
        // always returns false.
        $this->assertFalse($result);
    }
    
    public function testBackwardsSimpleException(): void
    {
        Rollbar::init(self::$simpleConfig);
        
        $uuid = null;
        try {
            throw new \Exception("test exception");
        } catch (\Exception $e) {
            $uuid = Rollbar::report_exception($e);
        }

        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }

    public function testBackwardsFlush(): void
    {
        Rollbar::init(self::$simpleConfig);

        Rollbar::flush();
        $this->assertTrue(true);
    }
    
    public function testConfigure(): void
    {
        $expected = 'expectedEnv';
        
        Rollbar::init(self::$simpleConfig);
        
        // functionality under test
        Rollbar::configure(array(
            'environment' => $expected
        ));
        
        // assertion
        $logger = Rollbar::logger();
        $dataBuilder = $logger->getDataBuilder();
        $data = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $payload = new Payload($data, self::$simpleConfig['access_token']);
        
        $this->assertEquals($expected, $payload->getData()->getEnvironment());
    }
    
    public function testEnable(): void
    {
        Rollbar::init(self::$simpleConfig);
        $this->assertTrue(Rollbar::enabled());
        
        Rollbar::disable();
        $this->assertTrue(Rollbar::disabled());
        
        Rollbar::enable();
        $this->assertTrue(Rollbar::enabled());
        
        Rollbar::init(array_merge(
            self::$simpleConfig,
            array('enabled' => false)
        ));
        $this->assertTrue(Rollbar::disabled());
        
        Rollbar::configure(array('enabled' => true));
        $this->assertTrue(Rollbar::enabled());
        
        Rollbar::configure(array('enabled' => false));
        $this->assertTrue(Rollbar::disabled());
    }

    public function testLogUncaughtUnsetLogger(): void
    {
        $sut = new Rollbar;
        $result = $sut->logUncaught('level', new \Exception);
        $expected = new Response(0, "Rollbar Not Initialized");
        $this->assertEquals($expected, $result);
    }

    public function testLogUncaught(): void
    {
        $sut = new Rollbar;
        Rollbar::init(self::$simpleConfig);
        $logger = Rollbar::logger();
        $toLog = new \Exception;
        $result = Rollbar::logUncaught(Level::ERROR, $toLog);
        $this->assertEquals(200, $result->getStatus());
        $this->assertObjectNotHasAttribute('uncaught', $toLog);
    }
}
