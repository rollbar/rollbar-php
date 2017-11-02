<?php namespace Rollbar;

use Rollbar\Rollbar;
use Rollbar\Payload\Level;

/**
 * Usage of static method Rollbar::logger() is intended here.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RollbarTest extends BaseRollbarTest
{
    
    public function __construct()
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['environment'] = 'test';
        
        parent::__construct();
    }

    private static $simpleConfig = array();

    private static function clearLogger()
    {
        $reflLoggerProperty = new \ReflectionProperty('Rollbar\Rollbar', 'logger');
        $reflLoggerProperty->setAccessible(true);
        $reflLoggerProperty->setValue(null);
    }
    
    public static function setupBeforeClass()
    {
        self::clearLogger();
    }

    public function tearDown()
    {
        self::clearLogger();
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
      
        Rollbar::log(Level::INFO, 'testing info level');
      
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
        
        $this->assertEquals(
            "some value",
            $payload->getData()->getBody()->getValue()->some_key
        );
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

    public function testExceptionHandler($exception = null)
    {
        if ($exception) {
            $backtrace = debug_backtrace();
            $this->assertEquals('exceptionHandler', $backtrace[2]['function']);
            return;
        }
        set_exception_handler(array($this, 'testExceptionHandler'));
        Rollbar::setupExceptionHandling();
        Rollbar::exceptionHandler(new \Exception());
        $handler = set_exception_handler('Rollbar\Rollbar::exceptionHandler');
        $this->assertEquals('testExceptionHandler', $handler[1]);
    }
}
