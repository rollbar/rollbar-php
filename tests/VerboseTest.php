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
 * The log mocking is achieved by mocking out the `handle`
 * method of the log handler used in the `verbose_logger`.
 *
 * @package Rollbar
 * @author Artur Moczulski <artur.moczulski@gmail.com>
 * @author Rollbar, Inc.
 */
class VerbosityTest extends BaseRollbarTest
{

    /**
     * Prepare session
     *
     * @return void
     */
    public function setUp()
    {
        $_SESSION = array();
        parent::setUp();
    }
    
    /**
     * Clean up Rollbar and the verbose logger handler mock for
     * the next test
     *
     * @return void
     */
    public function tearDown()
    {
        $this->verboseHandlerMock = null;
        Rollbar::destroy();
        parent::tearDown();
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log with
     * `enabled` == true.
     *
     * @return void
     */
    public function testRollbarLoggerEnabled()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "enabled" => true
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Attempting to log: \[warning\] Testing PHP Notifier/',
                    \Psr\Log\LogLevel::INFO
                );
                $unitTest->expectLog(
                    1,
                    '/Occurrence/',
                    \Psr\Log\LogLevel::INFO
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log with
     * `enabled` == false.
     *
     * @return void
     */
    public function testRollbarLoggerDisabled()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "enabled" => false
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(0, '/Rollbar is disabled/', \Psr\Log\LogLevel::NOTICE);
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log with
     * an invalid log level passed in the method call.
     *
     * @return void
     */
    public function testRollbarLoggerInvalidLogLevel()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php"
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(0, '/Invalid log level \'nolevel\'\./', \Psr\Log\LogLevel::ERROR);
            },
            'nolevel' // rollbar message level
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log when an
     * occurrence gets ignored for whatever reason.
     *
     * @return void
     */
    public function testRollbarLoggerInternalCheckIgnored()
    {
        $unitTest = $this;
        $errorReporting = \error_reporting();
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php"
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(2, '/Occurrence ignored/', \Psr\Log\LogLevel::INFO);
            },
            \Psr\Log\LogLevel::INFO, // rollbar message level
            function () {
            // test setup
                \error_reporting(0);
            },
            function () use ($errorReporting) {
            // test teardown
                \error_reporting($errorReporting);
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log when an
     * occurrence gets ignored due to check ignore
     *
     * @return void
     */
    public function testRollbarLoggerCheckIgnored()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "check_ignore" => function () {
                    return true;
                }
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(2, '/Occurrence ignored/', \Psr\Log\LogLevel::INFO);
            },
            \Psr\Log\LogLevel::INFO // rollbar message level
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log when
     * `max_items` is reached.
     *
     * @return void
     */
    public function testRollbarLoggerSendMaxItems()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "max_items" => 0
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    1,
                    '/Maximum number of items per request has been reached.*/',
                    \Psr\Log\LogLevel::WARNING
                );
            },
            \Psr\Log\LogLevel::INFO // rollbar message level
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for adding
     * occurrences to the queue when `batched` == true.
     *
     * @return void
     */
    public function testRollbarLoggerSendBatched()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "batched" => true
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    1,
                    '/Added payload to the queue \(running in `batched` mode\)\./',
                    \Psr\Log\LogLevel::DEBUG
                );
            },
            \Psr\Log\LogLevel::INFO // rollbar message level
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::flush
     *
     * @return void
     */
    public function testRollbarLoggerFlush()
    {
        $unitTest = $this;
        $rollbarLogger = $this->verboseRollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        $this->configurableObjectVerbosityTest(
            $rollbarLogger,
            function () use ($rollbarLogger) {
            // logic under test
                $rollbarLogger->flush();
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Queue flushed/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for reports
     * rejected by the SDK (response status == 0).
     *
     * @return void
     */
    public function testRollbarLoggerResponseStatusZero()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "check_ignore" => function () {
                    return true;
                }
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    3,
                    '/Occurrence rejected by the SDK: .*/',
                    \Psr\Log\LogLevel::ERROR
                );
            },
            \Psr\Log\LogLevel::INFO // rollbar message level
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for reports
     * rejected by the API (response status >= 400).
     *
     * @return void
     */
    public function testRollbarLoggerResponseStatusError()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php",
                "endpoint" => "https://api.rollbar.com/api/foo/"
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    1,
                    '/Occurrence rejected by the API: .*/',
                    \Psr\Log\LogLevel::ERROR
                );
            },
            \Psr\Log\LogLevel::INFO // rollbar message level
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for reports
     * successfully processed.
     *
     * @return void
     */
    public function testRollbarLoggerResponseStatusSuccess()
    {
        $unitTest = $this;
        $this->rollbarLogTest(
            array( // config
                "access_token" => $this->getTestAccessToken(),
                "environment" => "testing-php"
            ),
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    1,
                    '/Occurrence successfully logged/',
                    \Psr\Log\LogLevel::INFO
                );
            },
            \Psr\Log\LogLevel::INFO // rollbar message level
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::internalCheckIgnored
     * when error_reporting === 0.
     *
     * @return void
     */
    public function testRollbarConfigInternalCheckIgnoredShouldSuppress()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));
        $errorReporting = \error_reporting();

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config) {
            // logic under test
                $config->internalCheckIgnored(\Psr\Log\LogLevel::WARNING, "Some message");
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Ignoring \(error reporting has been disabled in PHP config\)/',
                    \Psr\Log\LogLevel::DEBUG
                );
            },
            function () {
            // test setup
                \error_reporting(0);
            },
            function () use ($errorReporting) {
            // test cleanup

                \error_reporting($errorReporting);
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::internalCheckIgnored when an
     * occurrence gets ignored due to occurrence level being
     * too low (`minimum_level` < log_level).
     *
     * @return void
     */
    public function testRollbarConfigInternalCheckIgnoredLevelTooLow()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "minimum_level" => \Psr\Log\LogLevel::ERROR
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config) {
            // logic under test
                $config->internalCheckIgnored(\Psr\Log\LogLevel::WARNING, "Some message");
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Occurrence\'s level is too low/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreError when
     * `use_error_reporting` == true and the error level is
     * below allowed error_reporting() level.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreErrorErrorReporting()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "use_error_reporting" => true
        ));
        $errorReporting = \error_reporting();

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config) {
            // logic under test
                $config->shouldIgnoreError(\E_WARNING);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Ignore \(error below allowed error_reporting level\)/',
                    \Psr\Log\LogLevel::DEBUG
                );
            },
            function () {
            // test setup
                \error_reporting(\E_ERROR);
            },
            function () use ($errorReporting) {
            // test tear down
                \error_reporting($errorReporting);
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreError when
     * `included_errno` is set.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreErrorIncludedErrno()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "included_errno" => \E_WARNING
        ));
        $errorReporting = \error_reporting();

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config) {
            // logic under test
                $config->shouldIgnoreError(\E_ERROR);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Ignore due to included_errno level/',
                    \Psr\Log\LogLevel::DEBUG
                );
            },
            function () {
            // test setup
                \error_reporting(0);
            },
            function () use ($errorReporting) {
            // test tear down
                \error_reporting($errorReporting);
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreError when
     * the error is skipped due to error sample rates.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreErrorErrorSampleRates()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "error_sample_rates" => array(
                \E_WARNING => 0
            )
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config) {
            // logic under test
                $config->shouldIgnoreError(\E_WARNING);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Skip due to error sample rating/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreException when
     * the exception is skipped due to exception sample rates.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreException()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "exception_sample_rates" => array(
                'Exception' => 0
            )
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config) {
            // logic under test
                $config->shouldIgnoreException(new \Exception());
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Skip exception due to exception sample rating/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due to custom
     * `check_ignore` logic.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnored()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "check_ignore" => function () {
                return true;
            }
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config, $unitTest) {
            // logic under test
                $dataMock = $unitTest->getMockBuilder('\Rollbar\Payload\Data')
                    ->disableOriginalConstructor()
                    ->getMock();
                $dataMock->method('getLevel')->willReturn(\Rollbar\Payload\Level::INFO());
                $payloadMock = $unitTest->getMockBuilder('\Rollbar\Payload\Payload')
                    ->disableOriginalConstructor()
                    ->getMock();
                $payloadMock->method('getData')->willReturn($dataMock);
                $config->checkIgnored(
                    $payloadMock,
                    $unitTest->getTestAccessToken(),
                    $payloadMock,
                    false
                );
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Occurrence ignored due to custom check_ignore logic/',
                    \Psr\Log\LogLevel::INFO
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due an exception
     * in the custom check_ginore logic.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnoredException()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "check_ignore" => function () {
                throw new \Exception();
            }
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config, $unitTest) {
            // logic under test
                $data = $config->getRollbarData(\Rollbar\Payload\Level::INFO, 'some message', array());
                $payload = new \Rollbar\Payload\Payload($data, $unitTest->getTestAccessToken());
                $config->checkIgnored($payload, $unitTest->getTestAccessToken(), 'some message', false);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Exception occurred in the custom checkIgnore logic:.*/',
                    \Psr\Log\LogLevel::ERROR
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due the message
     * being below `minimum_level`.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnoredPayloadLevelTooLow()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "minimum_level" => \Rollbar\Payload\Level::ERROR
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config, $unitTest) {
            // logic under test
                $data = $config->getRollbarData(\Rollbar\Payload\Level::INFO, 'some message', array());
                $payload = new \Rollbar\Payload\Payload($data, $unitTest->getTestAccessToken());
                $config->checkIgnored($payload, $unitTest->getTestAccessToken(), 'some message', false);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Occurrence\'s level is too low/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due the
     * custom `filter`.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnoredFilter()
    {
        $unitTest = $this;
        $filterMock = $this->getMockBuilder('\Rollbar\FilterInterface')->getMock();
        $filterMock->method('shouldSend')->willReturn(true);

        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "filter" => $filterMock
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config, $unitTest) {
            // logic under test
                $data = $config->getRollbarData(\Rollbar\Payload\Level::INFO, 'some message', array());
                $payload = new \Rollbar\Payload\Payload($data, $unitTest->getTestAccessToken());
                $config->checkIgnored($payload, $unitTest->getTestAccessToken(), 'some message', false);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Custom filter result: true/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::send due the
     * custom `transmit` == false.
     *
     * @return void
     */
    public function testRollbarConfigSendTransmit()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "transmit" => false
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config, $unitTest) {
            // logic under test
                $encoded = new \Rollbar\Payload\EncodedPayload(array());
                $config->send($encoded, $unitTest->getTestAccessToken());
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Not transmitting \(transmitting disabled in configuration\)/',
                    \Psr\Log\LogLevel::WARNING
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::sendBatch due the
     * custom `transmit` == false.
     *
     * @return void
     */
    public function testRollbarConfigSendBatchTransmit()
    {
        $unitTest = $this;
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "transmit" => false,
            "batched" => true
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config) {
            // logic under test
                $batch = array();
                $config->sendBatch($batch, null);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Not transmitting \(transmitting disabled in configuration\)/',
                    \Psr\Log\LogLevel::WARNING
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::handleResponse with
     * custom `responseHandler`.
     *
     * @return void
     */
    public function testRollbarConfigHandleResponse()
    {
        $unitTest = $this;
        $responseHandlerMock = $this->getMockBuilder('\Rollbar\ResponseHandlerInterface')->getMock();
        $config = $this->verboseRollbarConfig(array( // config
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "responseHandler" => $responseHandlerMock
        ));

        $this->configurableObjectVerbosityTest(

            $config,
            function () use ($config, $unitTest) {
            // logic under test
                $payloadMock = $unitTest->getMockBuilder('\Rollbar\Payload')
                    ->disableOriginalConstructor()
                    ->getMock();
                $responseMock = $unitTest->getMockBuilder('\Rollbar\Response')
                    ->disableOriginalConstructor()
                    ->getMock();
                $config->handleResponse($payloadMock, $responseMock);
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    0,
                    '/Applying custom response handler: .*/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * Test verbosity of \Rollbar\Truncation\Truncation::registerStrategy
     * in truncate method.
     *
     * @return void
     */
    public function testRollbarTruncation()
    {
        $unitTest = $this;
        $rollbarLogger = $this->verboseRollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php"
        ));

        $this->configurableObjectVerbosityTest(

            $rollbarLogger,
            function () use ($rollbarLogger) {
            // logic under test
                $rollbarLogger->log(
                    \Rollbar\Payload\Level::INFO,
                    \str_repeat("x", \Rollbar\Truncation\Truncation::MAX_PAYLOAD_SIZE),
                    array()
                );
            },
            function () use ($unitTest) {
            // verbosity expectations
                $unitTest->expectLog(
                    1,
                    '/Applying truncation strategy .*/',
                    \Psr\Log\LogLevel::DEBUG
                );
            }
        );
    }

    /**
     * @var mock $verboseHandlerMock The verboser log handler used for
     * verbose logging in tests.
     */
    private $verboseHandlerMock;

    /**
     * Test helper for creating \Rollbar\RollbarLogger or
     * \Rollbar\Config objects. It also sets up
     * the $this->verboseHandlerMock to the one used in
     * the created object.
     *
     * @param array $config Config array used to configure
     * the $object.
     * @param \Rollbar\RollbarLogger|\Rollbar\Config $object
     * Object to be set up for the test.
     */
    private function prepareForLogMocking($config, $object)
    {
        $verboseLogger = new \Monolog\Logger('rollbar.verbose.test');

        $object->configure(array_merge($config, array(
            'verbose_logger' => $verboseLogger
        )));

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

        return $object;
    }

    /**
     * This is a convenience method for creating properly configured
     * Rollbar config objects for testing verbosity. It also sets up
     * the $this->verboseHandlerMock to the one used in the created
     * Rollbar logger.
     *
     * @param array $config Configuration options for Rollbar
     * @return \Rollbar\Config
     */
    private function verboseRollbarConfig($config)
    {
        return $this->prepareForLogMocking(
            $config,
            new \Rollbar\Config($config)
        );
    }

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
        return $this->prepareForLogMocking(
            $config,
            new \Rollbar\RollbarLogger($config)
        );
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
        return $this->callback(function ($record) use ($messageRegEx, $level) {
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
    public function expectLog($at, $messageRegEx, $level, $handlerMock = null)
    {
        if ($handlerMock === null) {
            $handlerMock = $this->verboseHandlerMock;
        }

        $handlerMock
            ->expects($this->at($at))
            ->method('handle')
            ->with(
                $this->withLogParams($messageRegEx, $level)
            );
    }

    /**
     * Convenience method to expect a quiet verbose log handler mock.
     *
     * @param mock|null $handlerMock (optional) The handler mock on which to set the
     * expectations.
     */
    public function expectQuiet($handlerMock = null)
    {
        if ($handlerMock === null) {
            $handlerMock = $this->verboseHandlerMock;
        }

        $handlerMock
            ->expects($this->never())
            ->method('handle');
    }

    /**
     * Test helper providing a quiet and verbose scenario testing
     * for given functionality. Passing `verbose` config option
     * to the initial config is not needed as the method takes
     * care of performing assertions in both quiet and verbose scenarios.
     *
     * @param \Rollbar\RollbarLogger|\Rollbar\Config $object Object under test.
     * @param callback $test Logic under test
     * @param callback $verboseExpectations A callback with
     * expectations to be set on the verbose logger handler mock
     * in the verbose scenario.
     * @param callback $pre (optional) Logic to be executed before test.
     * @param callback $post (optional) Logic to be executed after the test
     */
    private function configurableObjectVerbosityTest(
        $object,
        $test,
        $verboseExpectations,
        $pre = null,
        $post = null
    ) {
        $unitTest = $this;
        // Quiet scenario
        $this->prepareForLogMocking(
            array('verbose' => \Rollbar\Config::VERBOSE_NONE),
            $object
        );
        $this->withTestLambdas(
            $test,
            function () use ($unitTest) {
                $unitTest->expectQuiet();
            },
            $pre,
            $post
        );

        // Verbose scenario
        $this->prepareForLogMocking(
            array('verbose' => \Psr\Log\LogLevel::DEBUG),
            $object
        );
        $this->withTestLambdas(
            $test,
            $verboseExpectations,
            $pre,
            $post
        );
    }

    /**
     * Test helper for performing verbosity tests
     *
     * @param callback $test Logic under test.
     * @param callback $expectations Logic with expectations.
     * @param callback $pre (optional) Test set up.
     * @param callback $post (optional) Test tear down.
     */
    private function withTestLambdas(
        $test,
        $expectations,
        $pre = null,
        $post = null
    ) {
        if ($pre !== null) {
            $pre();
        }

        $expectations();

        $test();

        if ($post !== null) {
            $post();
        }
    }

    /**
     * Convenience test helper for a Rollbar logger log test with
     * a verbose logger handler mock. Passing `verbose` config option
     * to the initial config is not needed as
     * `configurableObjectVerbosityTest` takes care of performing
     * assertions in both quiet and verbose scenarios.
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
        $rollbarLogger = $this->verboseRollbarLogger($config);

        $this->configurableObjectVerbosityTest(
            $rollbarLogger,
            function () use ($rollbarLogger, $messageLevel) {
                try {
                    $rollbarLogger->log($messageLevel, "Testing PHP Notifier", array());
                } catch (\Exception $exception) {
                } // discard exceptions - that's what's under test here
            },
            $expectations,
            $pre,
            $post
        );
    }
}
