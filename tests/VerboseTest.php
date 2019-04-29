<?php

namespace Rollbar;

use \Rollbar\Payload\Level as Level;

/**
 * \Rollbar\VerboseTest tests the verbosity of the SDK.
 * 
 * This test doesn't focus on testing one particular class.
 * Instead it tests `verbose` functionality across multiple
 * classes.
 * 
 * @package Rollbar
 * @author Artur Moczulski <artur.moczulski@gmail.com>
 * @author Rollbar, Inc.
 */
class VerbosityTest extends BaseRollbarTest
{

    public function setUp()
    {
        $_SESSION = array();
        parent::setUp();
    }
    
    public function tearDown()
    {
        $this->verboseHandlerMock = null;
        Rollbar::destroy();
        parent::tearDown();
    }

    public function testRollbarLoggerEnabled()
    {
        $this->rollbarLogVerbosityTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "enabled" => true
            ),
            function() { // verbosity expectations
                $this->expectLog(0, '/Attempting to log: \[warning\] Testing PHP Notifier/', \Psr\Log\LogLevel::INFO);
                $this->expectLog(1, '/Occurrence successfully logged/', \Psr\Log\LogLevel::INFO);
            }
        );
    }

    public function testRollbarLoggerDisabled()
    {
        $this->rollbarLogVerbosityTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "enabled" => false
            ),
            function() { // verbosity expectations
                $this->expectLog(0, '/Rollbar is disabled/', \Psr\Log\LogLevel::NOTICE);
            }
        );
    }    

    public function testRollbarLoggerInvalidLogLevel()
    {
        $this->rollbarLogVerbosityTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php"
            ),
            function() { // verbosity expectations
                $this->expectLog(0, '/Invalid log level \'nolevel\'\./', \Psr\Log\LogLevel::ERROR);
            },
            'nolevel' // rollbar message level
        );
    }

    public function testInternalCheckIgnored()
    {
        $errorReporting = 0;
        $this->rollbarLogVerbosityTest(
            
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php"
            ),
            
            function() { // verbosity expectations
                $this->expectLog(
                    2, 
                    '/Occurrence ignored/', 
                    \Psr\Log\LogLevel::INFO
                );
            }, 

            \Psr\Log\LogLevel::WARNING, // rollbar log message level

            function() use ($errorReporting) { // test setup

                $errorReporting = \error_reporting();
                \error_reporting(0);

            }, function() use ($errorReporting) { // test cleanup

                \error_reporting($errorReporting);
            }
        );
    }


    /**
     * @var mock $verboseHandlerMock The verboser log handler used for
     * verbose logging in tests.
     */
    private $verboseHandlerMock;

    /**
     * This is a convenience method for creating properly configured
     * Rollbar logger objects for testing verbosity. It also sets up
     * the $this->verboseHandlerMock to the one used in the created
     * Rollbar logger.
     * 
     * @param array $config Configuration options for Rollbar
     * @return \Rollbar\RollbarLogger
     */
    private function verboseRollbarLogger($config)
    {
        $verboseLogger = new \Monolog\Logger('rollbar.verbose.test');
        $config['verbose_logger'] = $verboseLogger;

        $rollbarLogger = new RollbarLogger($config);

        $verbose = isset($config['verbose']) ? 
            $config['verbose'] : 
            \Rollbar\Config::VERBOSE_NONE;

        if ($verbose == \Rollbar\Config::VERBOSE_NONE) {
            $verbose = \Rollbar\Config::VERBOSE_NONE_INT;
        } else {
            $verbose = \Monolog\Logger::toMonologLevel($verbose);
        }

        $handlerMock = $this->getMockBuilder('\Monolog\Handler\AbstractHandler')
            ->setMethods(array('handle'))
            ->getMock();
        $handlerMock->setLevel($verbose);

        $verboseLogger->setHandlers(array($handlerMock));

        $this->verboseHandlerMock = $handlerMock;

        return $rollbarLogger;
    }

    /**
     * Convenience method for asserting verbose logging calls on the
     * handler mock.
     * 
     * @param string $messageRegEx Regular expression against which the
     * log message will be asserted.
     * @param string $level The level of the log recorded which will
     * be asserted.
     */
    private function withLogParams($messageRegEx, $level)
    {
        return $this->callback(function($record) use ($messageRegEx, $level) { 
            return 
                \preg_match($messageRegEx, $record['message']) &&
                strtolower($record['level_name']) == strtolower($level);
        });
    }

    /**
     * Convenience method to expect verbose log messages
     * on the verbose log handler mock.
     * 
     * @param integer $at The incrementing number indicating the order
     * of the log message.
     * @param string $messageRegEx Regex against which the log message
     * will be asserted.
     * @param string $level The log level against which the log will
     * be asserted.
     * @param mock|null $handlerMock (optional) The handler mock on which to set the
     * expectations.
     */
    private function expectLog($at, $messageRegEx, $level, $handlerMock = null)
    {
        if ($handlerMock === null) {
            $handlerMock = $this->verboseHandlerMock;
        }

        $handlerMock
            ->expects($this->at($at))
            ->method('handle')
            ->with(
                $this->withLogParams($messageRegEx, $level),
            );
    }

    /**
     * Convenience method to expect a quiet verbose log handler mock.
     * 
     * @param mock|null $handlerMock (optional) The handler mock on which to set the
     * expectations.
     */
    private function expectQuiet($handlerMock = null)
    {
        if ($handlerMock === null) {
            $handlerMock = $this->verboseHandlerMock;
        }

        $handlerMock
            ->expects($this->never())
            ->method('handle');
    }

    /**
     * Conenience test helper for Rollbar logger log test with
     * a verbose logger handler mock that checks against the
     * quiet and verbose scenarios.
     * 
     * @param array $config Configuration for Rollbar logger.
     * @param callback $verboseExpectations A callback with
     * expectations to be set on the verbose logger handler mock
     * in the verbose scenario.
     * @param string $messageLevel (optional) The level of the Rollbar log
     * message invoked.
     * @param callback $pre (optional) Logic to be executed before test.
     * @param callback $post (optional) Logic to be executed after the test
     */
    private function rollbarLogVerbosityTest(
        $config,
        $verboseExpectations,
        $messageLevel = Level::WARNING,
        $pre = null,
        $post = null
    ) {
        // Quiet scenario
        $config['verbose'] = \Rollbar\Config::VERBOSE_NONE;
        $this->rollbarLogTest(
            $config,
            function() {
                $this->expectQuiet();
            },
            $messageLevel,
            $pre,
            $post
        );

        // Verbose scenario
        $config['verbose'] = \Psr\Log\LogLevel::DEBUG;
        $this->rollbarLogTest($config, $verboseExpectations, $messageLevel, $pre, $post);
    }

    /**
     * Convenience test helper for a Rollbar logger log test with
     * a verbose logger handler mock.
     * 
     * @param array $config Configuration for Rollbar logger.
     * @param callback $expectations A callback with expectations to be
     * set on the verbose logger handler mock.
     * @param string $messageLevel (optional) The level of the Rollbar log
     * message invoked.
     * @param callback $pre (optional) Logic to be executed before test.
     * @param callback $post (optional) Logic to be executed after the test
     */
    private function rollbarLogTest(
        $config,
        $expectations,
        $messageLevel = Level::WARNING,
        $pre = null,
        $post = null
    ) {
        if ($pre !== null) {
            $pre();
        }

        $rollbarLogger = $this->verboseRollbarLogger($config);

        $expectations();

        try {
            $rollbarLogger->log($messageLevel, "Testing PHP Notifier", array());
        } catch(\Exception $exception) {} // discard exceptions - that's what's under test here

        if ($post !== null) {
            $post();
        }
    }
}