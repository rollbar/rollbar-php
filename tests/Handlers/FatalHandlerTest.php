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
    
    /**
     * This is only applicable to PHP < 7. Fatal errors are thrown as Error-type
     * exceptions starting from PHP 7 and thus will be handled by the exception
     * handler. For older PHP, fatal errors need to be handled through the
     * shutdown functions - this tests is.
     */
    public function testRegisterAndHandle()
    {
        if (version_compare(PHP_VERSION, '7', '>=')) {
            $this->markTestSkipped("Fatal Errors are not used on PHP 7+.");
        }
        
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped("Fatal Errors are not used on HHVM.");
        }
        
        $test = new FatalHandlerTest('FatalHandlerTest');
        $test->setName('handleInternal');
        $test->setRunTestInSeparateProcess(true);
        $test->setPreserveGlobalState(false);
        
        $result = $test->run();
        
        $errors = $result->errors();
        
        $trace = $errors[0]->thrownException()->getTrace();
        
        $stdOut = $trace[0]['args'][2];
        
        /**
         * Assert that the standard output contains the log message generated
         * by StdOutLogger test helper used in handleInternal.
         */
        $expected = "[Rollbar\TestHelpers\StdOutLogger: critical] exception ".
                    "'Rollbar\ErrorWrapper' with message 'Call to a member ".
                    "function noMethod()";
                    
        $this->assertTrue(
            strpos($stdOut, $expected) !== false,
            'Failed asserting that the fatal error has triggered a log entry.'
        );
    }
    
    /**
     * Perform the test with a special StdOutLogger helper. Rollbar messages
     * will be printed to std out and later picked up for an assertion in
     * testRegisterAndHandle test. This way we can verify that the fatal handler
     * triggers the log() method of the provider logger.
     */
    public function handleInternal()
    {
        $logger = new StdOutLogger(self::$simpleConfig);
        
        $handler = new FatalHandler($logger);
        $handler->register();
        
        // Trigger the fatal error
        $null = null;
        $null->noMethod();
    }
}
