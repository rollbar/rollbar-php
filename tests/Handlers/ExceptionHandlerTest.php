<?php namespace Rollbar\Handlers;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Rollbar\BaseRollbarTest;
use Rollbar\Handlers\ExceptionHandler;

class ExceptionHandlerTest extends BaseRollbarTest
{
    public function __construct($name = null, $data = array(), $dataName = null)
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['included_errno'] = E_ALL;
        self::$simpleConfig['environment'] = 'test';
        
        parent::__construct($name, $data, $dataName);
    }

    private static array $simpleConfig = array();
    
    /**
     * It's impossible to throw an uncaught exception with PHPUnit and thus
     * trigger the exception handler automatically. To overcome this limitation,
     * this test invokes the handle() methd manually with an assertion in the
     * previously set exception handler.
     */
    public function testPreviousExceptionHandler(): void
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
    public function testSetup(): void
    {
        $handler = $this->getMockBuilder(ExceptionHandler::class)
                        ->setConstructorArgs(array(new RollbarLogger(self::$simpleConfig)))
                        ->setMethods(array('handle'))
                        ->getMock();
        
        $handler->expects($this->once())
                ->method('handle');
                
        $handler->register();
        
        $setExceptionHandler = set_exception_handler(function () {
        });
        
        $handler = $setExceptionHandler[0];
        $method = $setExceptionHandler[1];
        
        $handler->$method();
    }
    
    public function testHandle(): void
    {
        set_exception_handler(function () {
        });
        
        $logger = $this->getMockBuilder(RollbarLogger::class)
                        ->setConstructorArgs(array(self::$simpleConfig))
                        ->setMethods(array('report'))
                        ->getMock();
        
        $logger->expects($this->once())
                ->method('report');
        
        $handler = new ExceptionHandler($logger);
        $handler->register();
        
        $handler->handle(new \Exception());
        
        set_exception_handler(function () {
        });
    }

    /**
     * This test is specifically for the deprecated dynamic properties in PHP 8.2. We were setting a property named
     * "isUncaught" on the exception object, which is now deprecated. This test ensures that we are no longer setting
     * that property.
     *
     * @return void
     */
    public function testDeprecatedDynamicProperties(): void
    {
        // Set error reporting level and error handler to capture deprecation
        // warnings.
        $prev = error_reporting(E_ALL);
        $errors = array();
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$errors) {
            $errors[] = array(
                'errno'   => $errno,
                'errstr'  => $errstr,
                'errfile' => $errfile,
                'errline' => $errline,
            );
        });
        $handler = new ExceptionHandler(new RollbarLogger(self::$simpleConfig));
        $handler->register();

        $handler->handle(new \Exception());
        restore_error_handler();
        error_reporting($prev);

        // self::assertSame used instead of self::assertSame so the contents of
        // $errors are printed in the test output.
        self::assertSame([], $errors);
    }
}
