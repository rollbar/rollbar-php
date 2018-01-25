<?php namespace Rollbar\Handlers;

use \Rollbar\Rollbar;
use \Rollbar\RollbarLogger;
use \Rollbar\BaseRollbarTest;
use \Rollbar\TestHelpers\StdOutLogger;

class FatalHandlerTest extends BaseRollbarTest
{
    public function __construct($name = null, $data = array(), $dataName = null)
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['included_errno'] = E_ALL;
        self::$simpleConfig['environment'] = 'test';
        
        parent::__construct($name, $data, $dataName);
    }

    private static $simpleConfig = array();
    
    public function testRegisterAndHandle()
    {
        $test = new FatalHandlerTest('FatalHandlerTest');
        $test->setName('handleInternal');
        $test->setRunTestInSeparateProcess(true);
        $test->setPreserveGlobalState(false);
        
        $result = $test->run();
        
        $errors = $result->errors();
        
        $stdOut = $errors[0]->thrownException()->getTrace()[0]['args'][2];
        
        /**
         * Assert that the standard output contains the log message generated
         * by StdOutLogger test helper used in handleInternal.
         */
        $expected = "[Rollbar\TestHelpers\StdOutLogger: critical] exception ".
                    "'Rollbar\ErrorWrapper' with message 'Call to a member ".
                    "function noMethod() on null'";
                    
        $this->assertTrue(strpos($stdOut, $expected) !== false);
    }
    
    /**
     * This only works on PHP 5.6. PHP 7+ converts errors to exceptions which
     * results in error_get_last() returning null and not triggering $logger's
     * log() method.
     *
     * TODO: I have to investigate if this is going to be a problem only for
     * PHPUnit or is this going to affect the Rollbar PHP SDK itself.
     */
    public function handleInternal()
    {
        // if (version_compare(PHP_VERSION, '7', '<')) {
        //     $this->handleInternalPHP5();
        // }
        handleInternalPHP5();
    }
    
    /**
     * Perform the test with a special StdOutLogger helper. Rollbar messages
     * will be printed to std out and later picked up for an assertion in
     * testRegisterAndHandle test. This way we can verify that the fatal handler
     * triggers the log() method of the provider logger.
     */
    public function handleInternalPHP5()
    {
        $logger = new StdOutLogger(self::$simpleConfig);
        
        $handler = new FatalHandler($logger);
        $handler->register();
        
        // Trigger the fatal error
        $null = null;
        $null->noMethod();
    }
    
    /**
     * TODO: This is just copy and paste from ErrorHandlerTest but this method
     * needs to be implemented for FatalHandlerTest as well.
     */
    // public function testPreviousErrorHandler()
    // {
    //     $testCase = $this;
        
    //     set_error_handler(function () use ($testCase) {
            
    //         $testCase->assertTrue(true, "Previous error handler invoked.");
            
    //         set_error_handler(null);
    //     });
        
    //     Rollbar::init(self::$simpleConfig);
        
    //     @trigger_error(E_USER_ERROR);
    // }
}
