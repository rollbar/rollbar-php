<?php namespace Rollbar\Handlers;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Rollbar\BaseRollbarTest;
use Rollbar\Handlers\ErrorHandler;

/**
 * TODO: consider using $this->useErrorHandler to deal with stopping the
 * PHPUnit's error handler instead of set_error_handler()
 */
class ErrorHandlerTest extends BaseRollbarTest
{
    public function __construct($name = null, $data = array(), $dataName = null)
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['included_errno'] = E_ALL;
        self::$simpleConfig['environment'] = 'test';
        
        parent::__construct($name, $data, $dataName);
    }

    private static array $simpleConfig = array();
    
    public function testPreviousErrorHandler(): void
    {
        $testCase = $this;
        
        set_error_handler(function () use ($testCase) {
            
            $testCase->assertTrue(true, "Previous error handler invoked.");
            
            set_error_handler(null);
        });
        
        Rollbar::init(self::$simpleConfig);
        
        @trigger_error(E_USER_ERROR);
    }
    
    public function testRegister(): void
    {
        $handler = $this->getMockBuilder(ErrorHandler::class)
                        ->setConstructorArgs(array(new RollbarLogger(self::$simpleConfig)))
                        ->setMethods(array('handle'))
                        ->getMock();
                        
        $handler->expects($this->once())
                ->method('handle');
        
        $handler->register();
        
        trigger_error(E_USER_ERROR);
    }
    
    public function testHandle(): void
    {
        $logger = $this->getMockBuilder(RollbarLogger::class)
                        ->setConstructorArgs(array(self::$simpleConfig))
                        ->setMethods(array('report'))
                        ->getMock();
        
        $logger->expects($this->once())
                ->method('report');
        
        /**
         * Disable PHPUnit's error handler as it would get triggered as the
         * previously set error handler. No need for that here.
         */
        $phpunitHandler = set_error_handler(function () {
        });
        
        $handler = new ErrorHandler($logger);
        $handler->register();
        
        $handler->handle(E_USER_ERROR, "", "", "");
        
        /**
         * Clean up the error handler set up for this test.
         */
        set_error_handler($phpunitHandler);
    }
}
