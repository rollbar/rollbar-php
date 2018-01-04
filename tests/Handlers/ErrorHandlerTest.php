<?php namespace Rollbar\Handlers;

use \Rollbar\Rollbar;
use \Rollbar\RollbarLogger;
use \Rollbar\BaseRollbarTest;

class ErrorHandlerTest extends BaseRollbarTest
{
    public function __construct()
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['included_errno'] = E_ALL;
        self::$simpleConfig['environment'] = 'test';
        
        parent::__construct();
    }

    private static $simpleConfig = array();
    
    public function testPreviousErrorHandler()
    {
        $testCase = $this;
        
        set_error_handler(function() use ($testCase) {
            
            $testCase->assertTrue(true, "Previous error handler invoked.");
            
        });
        
        Rollbar::init(self::$simpleConfig);
        
        @trigger_error(E_USER_ERROR);
    }
    
    public function testRegister()
    {
        $handler = $this->getMockBuilder('Rollbar\\Handlers\\ErrorHandler')
                        ->setConstructorArgs(array(new RollbarLogger(self::$simpleConfig)))
                        ->setMethods(array('handle'))
                        ->getMock();
                        
        $handler->expects($this->once())
                ->method('handle');
        
        $handler->register();
        
        trigger_error(E_USER_ERROR);
        
    }
    
    public function testHandle()
    {
        $logger = $this->getMockBuilder('Rollbar\\RollbarLogger')
                        ->setConstructorArgs(array(self::$simpleConfig))
                        ->setMethods(array('log'))
                        ->getMock();
        
        $logger->expects($this->once())
                ->method('log');
        
        $handler = new ErrorHandler($logger);
        $handler->register();
        
        $handler->handle();
    }
}
