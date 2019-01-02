<?php namespace Rollbar;

use Rollbar\Rollbar;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;

/**
 * Usage of static method Rollbar::logger() is intended here.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RollbarTest extends BaseRollbarTest
{
    
    public function setUp()
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['environment'] = 'test';
    }

    private static $simpleConfig = array();
    
    public static function setupBeforeClass()
    {
        Rollbar::destroy();
    }
    
    public function testInitWithConfig()
    {
        Rollbar::init(self::$simpleConfig);
        
        $this->assertInstanceOf('Rollbar\RollbarLogger', Rollbar::logger());
        $this->assertAttributeEquals(new Config(self::$simpleConfig), 'config', Rollbar::logger());
    }
    
    public function testInitWithLogger()
    {
        $logger = $this->getMockBuilder('Rollbar\RollbarLogger')->disableOriginalConstructor()->getMock();

        Rollbar::init($logger);
        
        $this->assertSame($logger, Rollbar::logger());
    }
    
    public function testInitConfigureLogger()
    {
        $logger = $this->getMockBuilder('Rollbar\RollbarLogger')->disableOriginalConstructor()->getMock();
        $logger->expects($this->once())->method('configure')->with(self::$simpleConfig);

        Rollbar::init($logger);
        Rollbar::init(self::$simpleConfig);
    }
    
    public function testInitReplaceLogger()
    {
        Rollbar::init(self::$simpleConfig);

        $this->assertInstanceOf('Rollbar\RollbarLogger', Rollbar::logger());

        $logger = $this->getMockBuilder('Rollbar\RollbarLogger')->disableOriginalConstructor()->getMock();

        Rollbar::init($logger);

        $this->assertSame($logger, Rollbar::logger());
    }

    public function testLogException()
    {
        Rollbar::init(self::$simpleConfig);

        try {
            throw new \Exception('test exception');
        } catch (\Exception $e) {
            Rollbar::log(Level::ERROR, $e);
        }
        
        $this->assertTrue(true);
    }
    
    public function testLogMessage()
    {
        Rollbar::init(self::$simpleConfig);
      
        $response = Rollbar::log(Level::INFO, 'testing info level');
      
        $this->assertTrue(true);
    }
    
    public function testLogExtraData()
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
    
    public function testDebug()
    {
        $this->shortcutMethodTestHelper(Level::DEBUG);
    }
    
    public function testInfo()
    {
        $this->shortcutMethodTestHelper(Level::INFO);
    }
    
    public function testNotice()
    {
        $this->shortcutMethodTestHelper(Level::NOTICE);
    }
    
    public function testWarning()
    {
        $this->shortcutMethodTestHelper(Level::WARNING);
    }
    
    public function testError()
    {
        $this->shortcutMethodTestHelper(Level::ERROR);
    }
    
    public function testCritical()
    {
        $this->shortcutMethodTestHelper(Level::CRITICAL);
    }
    
    public function testAlert()
    {
        $this->shortcutMethodTestHelper(Level::ALERT);
    }
    
    public function testEmergency()
    {
        $this->shortcutMethodTestHelper(Level::EMERGENCY);
    }
    
    protected function shortcutMethodTestHelper($level)
    {
        $message = "shortcutMethodTestHelper: $level";
        
        $result = Rollbar::$level($message);
        $expected = Rollbar::log($level, $message);
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Below are backwards compatibility tests with v0.18.2
     */
    public function testBackwardsSimpleMessageVer()
    {
        Rollbar::init(self::$simpleConfig);

        $uuid = Rollbar::report_message("Hello world");
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }
    
    public function testBackwardsSimpleError()
    {
        set_error_handler(function () {
        }); // disable PHPUnit's error handler
        
        Rollbar::init(self::$simpleConfig);
        
        $result = Rollbar::report_php_error(E_ERROR, "Runtime error", "the_file.php", 1);
        // always returns false.
        $this->assertFalse($result);
    }
    
    public function testBackwardsSimpleException()
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

    public function testBackwardsFlush()
    {
        Rollbar::init(self::$simpleConfig);

        Rollbar::flush();
        $this->assertTrue(true);
    }
    
    public function testConfigure()
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
    
    public function testEnable()
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
}
