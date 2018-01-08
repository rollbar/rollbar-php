<?php namespace Rollbar\Handlers;

use \Rollbar\Rollbar;
use \Rollbar\RollbarLogger;
use \Rollbar\BaseRollbarTest;
use \Rollbar\Handlers\ExceptionHandler;

class ExceptionHandlerTest extends BaseRollbarTest
{
    public function __construct()
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['included_errno'] = E_ALL;
        self::$simpleConfig['environment'] = 'test';
        
        parent::__construct();
    }

    private static $simpleConfig = array();
    
    /**
     * It's impossible to throw an uncaught exception with PHPUnit and thus
     * trigger the exception handler automatically. To overcome this limitation,
     * this test invokes the handle() methd manually with an assertion in the
     * previously set exception handler.
     */
    public function testPreviousExceptionHandler()
    {
        $testCase = $this;
        
        set_exception_handler(function () use ($testCase) {
            
            $testCase->assertTrue(true, "Previous exception handler invoked.");
        });
        
        $handler = new ExceptionHandler(new RollbarLogger(self::$simpleConfig));
        $handler->register();
        
        $handler->handle(new \Exception());
    }
    
    /**
     * It's impossible to throw an uncaught exception with PHPUnit and thus
     * trigger the exception handler automatically. To overcome this limitation,
     * this test fetches the exception handler set by the setup method with
     * set_exception_handler() and invokes it manually with a mock expectation.
     */
    public function testSetup()
    {
        $handler = $this->getMockBuilder('Rollbar\\Handlers\\ExceptionHandler')
                        ->setConstructorArgs(array(new RollbarLogger(self::$simpleConfig)))
                        ->setMethods(array('handle'))
                        ->getMock();
        
        $handler->expects($this->once())
                ->method('handle');
                
        $handler->register();
        
        $setExceptionHandler = set_exception_handler(null);
        
        call_user_func($setExceptionHandler, null);
    }
    
    /**
     * This error should have expectedException. If you run phpunit with
     * --filter=testHandle and run this as a single test, the test fails as
     * expected with an unhandled exception. Unfortunately, for some reason,
     * this doesn't happen when you run the full TestSuite. That's why
     * there is no expectedException here.
     *
     * @expectedException \Exception
     */
    public function testHandle()
    {
        $logger = $this->getMockBuilder('Rollbar\\RollbarLogger')
                        ->setConstructorArgs(array(self::$simpleConfig))
                        ->setMethods(array('log'))
                        ->getMock();
        
        $logger->expects($this->once())
                ->method('log');
        
        $handler = new ExceptionHandler($logger);
        $handler->register();
        
        $handler->handle(new \Exception());
    }
}
