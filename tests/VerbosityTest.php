<?php

namespace Rollbar;

use Psr\Log\LogLevel;
use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Data;
use Rollbar\TestHelpers\ArrayLogger;

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
    private ArrayLogger|null $verboseLogger = null;

    /**
     * Prepare session
     *
     * @return void
     */
    public function setUp(): void
    {
        $_SESSION            = array();
        $this->verboseLogger = new ArrayLogger();
        parent::setUp();
    }

    /**
     * Clean up Rollbar and the verbose logger handler mock for the next test
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->verboseLogger = null;
        Rollbar::destroy();
        parent::tearDown();
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log with `enabled` == true.
     *
     * @return void
     */
    public function testRollbarLoggerEnabled(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            "enabled"        => true,
            'verbose_logger' => $this->verboseLogger,
        ]);

        Rollbar::log(Level::WARNING, "Testing PHP Notifier");

        $this->assertVerboseLogsConsecutive(
            ['level' => LogLevel::INFO, 'messageRegEx' => 'Attempting to log: \[warning\] Testing PHP Notifier'],
            ['level' => LogLevel::INFO, 'messageRegEx' => 'Occurrence'],
        );
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log with `enabled` == false.
     *
     * @return void
     */
    public function testRollbarLoggerDisabled(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            "enabled"        => false,
            'verbose_logger' => $this->verboseLogger,
        ]);

        Rollbar::log(Level::WARNING, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Rollbar is disabled', LogLevel::NOTICE);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log with an invalid log level passed in the method call.
     *
     * @return void
     */
    public function testRollbarLoggerInvalidLogLevel(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
        ]);

        $this->expectException(\Psr\Log\InvalidArgumentException::class);
        Rollbar::log('nolevel', "Testing PHP Notifier");

        $this->assertVerboseLogContains('Invalid log level \'nolevel\'\.', LogLevel::ERROR);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log when an occurrence gets ignored for whatever reason.
     *
     * @return void
     */
    public function testRollbarLoggerInternalCheckIgnored(): void
    {
        $errorReporting = \error_reporting();
        \error_reporting(0);

        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
        ]);
        Rollbar::log(LogLevel::INFO, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Occurrence ignored', LogLevel::INFO);

        \error_reporting($errorReporting);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log when an occurrence gets ignored due to check ignore
     *
     * @return void
     */
    public function testRollbarLoggerCheckIgnored(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'check_ignore'   => function () {
                return true;
            },
        ]);
        Rollbar::log(LogLevel::WARNING, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Occurrence ignored', LogLevel::INFO);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log when `max_items` is reached.
     *
     * @return void
     */
    public function testRollbarLoggerSendMaxItems(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'max_items'      => 0,
        ]);
        Rollbar::log(LogLevel::INFO, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Maximum number of items per request has been reached.', LogLevel::WARNING);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for adding occurrences to the queue when `batched` == true.
     *
     * @return void
     */
    public function testRollbarLoggerSendBatched(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'batched'        => true,
        ]);
        Rollbar::log(LogLevel::INFO, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Added payload to the queue \(running in `batched` mode\)\.', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::flush
     *
     * @return void
     */
    public function testRollbarLoggerFlush(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
        ]);
        Rollbar::log(LogLevel::WARNING, "Testing PHP Notifier");
        Rollbar::flush();
        $this->assertVerboseLogContains('Queue flushed', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for reports rejected by the SDK (response status == 0).
     *
     * @return void
     */
    public function testRollbarLoggerResponseStatusZero(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'check_ignore'   => function () {
                return true;
            },
        ]);
        Rollbar::log(LogLevel::INFO, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Occurrence rejected by the SDK:', LogLevel::ERROR);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for reports rejected by the API (response status >= 400).
     *
     * @return void
     */
    public function testRollbarLoggerResponseStatusError(): void
    {
        Rollbar::init([
            // Invalid access token should cause a 403 response.
            'access_token'   => '00000000000000000000000000000000',
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
        ]);
        Rollbar::log(LogLevel::INFO, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Occurrence rejected by the API: with status 403: ', LogLevel::ERROR);
    }

    /**
     * Test verbosity of \Rollbar\RollbarLogger::log for reports successfully processed.
     *
     * @return void
     */
    public function testRollbarLoggerResponseStatusSuccess(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
        ]);
        Rollbar::log(LogLevel::INFO, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Occurrence successfully logged', LogLevel::INFO);
    }

    /**
     * Test verbosity of \Rollbar\Config::internalCheckIgnored when error_reporting === 0.
     *
     * @return void
     */
    public function testRollbarConfigInternalCheckIgnoredShouldSuppress(): void
    {
        $errorReporting = \error_reporting();
        \error_reporting(0);

        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
        ]);
        Rollbar::logger()->getConfig()->internalCheckIgnored(LogLevel::WARNING, "Some message");
        $this->assertVerboseLogContains(
            'Ignoring \(error reporting has been disabled in PHP config\)',
            LogLevel::DEBUG,
        );

        \error_reporting($errorReporting);
    }

    /**
     * Test verbosity of \Rollbar\Config::internalCheckIgnored when an occurrence gets ignored due to occurrence level
     * being too low (`minimum_level` < log_level).
     *
     * @return void
     */
    public function testRollbarConfigInternalCheckIgnoredLevelTooLow(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'minimum_level'  => LogLevel::ERROR,
        ]);
        Rollbar::logger()->getConfig()->internalCheckIgnored(LogLevel::WARNING, "Some message");
        $this->assertVerboseLogContains('Occurrence\'s level is too low', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreError when `use_error_reporting` == true and the error level is
     * below allowed error_reporting() level.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreErrorErrorReporting(): void
    {
        $errorReporting = \error_reporting();
        \error_reporting(\E_ERROR);

        Rollbar::init([
            'access_token'        => $this->getTestAccessToken(),
            'environment'         => 'testing-php',
            'verbose_logger'      => $this->verboseLogger,
            'use_error_reporting' => true,
        ]);
        Rollbar::logger()->getConfig()->shouldIgnoreError(\E_WARNING);
        $this->assertVerboseLogContains('Ignore \(error below allowed error_reporting level\)', LogLevel::DEBUG);

        \error_reporting($errorReporting);
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreError when `included_errno` is set.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreErrorIncludedErrno(): void
    {
        $errorReporting = \error_reporting();
        \error_reporting(0);

        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'included_errno' => \E_WARNING,
        ]);
        Rollbar::logger()->getConfig()->shouldIgnoreError(\E_ERROR);
        $this->assertVerboseLogContains('Ignore due to included_errno level', LogLevel::DEBUG);

        \error_reporting($errorReporting);
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreError when the error is skipped due to error sample rates.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreErrorErrorSampleRates(): void
    {
        Rollbar::init([
            'access_token'       => $this->getTestAccessToken(),
            'environment'        => 'testing-php',
            'verbose_logger'     => $this->verboseLogger,
            'error_sample_rates' => array(
                \E_WARNING => 0,
            ),
        ]);
        Rollbar::logger()->getConfig()->shouldIgnoreError(\E_WARNING);
        $this->assertVerboseLogContains('Skip due to error sample rating', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\Config::shouldIgnoreException when the exception is skipped due to exception sample
     * rates.
     *
     * @return void
     */
    public function testRollbarConfigShouldIgnoreException(): void
    {
        Rollbar::init([
            'access_token'           => $this->getTestAccessToken(),
            'environment'            => 'testing-php',
            'verbose_logger'         => $this->verboseLogger,
            'exception_sample_rates' => array(
                'Exception' => 0,
            ),
        ]);
        Rollbar::logger()->getConfig()->shouldIgnoreException(new \Exception());
        $this->assertVerboseLogContains('Skip exception due to exception sample rating', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due to custom `check_ignore` logic.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnored(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'check_ignore'   => function () {
                return true;
            },
        ]);

        $dataMock = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataMock->method('getLevel')->willReturn(\Rollbar\LevelFactory::fromName(Level::INFO));

        $payloadMock = $this->getMockBuilder(Payload::class)->disableOriginalConstructor()->getMock();
        $payloadMock->method('getData')->willReturn($dataMock);

        Rollbar::logger()->getConfig()->checkIgnored($payloadMock, $payloadMock, false);
        $this->assertVerboseLogContains('Occurrence ignored due to custom check_ignore logic', LogLevel::INFO);
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due an exception in the custom check_ginore logic.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnoredException(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'check_ignore'   => function () {
                throw new \Exception();
            },
        ]);

        Rollbar::log(LogLevel::WARNING, "Testing PHP Notifier");
        $this->assertVerboseLogContains('Exception occurred in the custom checkIgnore logic:', LogLevel::ERROR);
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due the message being below `minimum_level`.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnoredPayloadLevelTooLow(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'minimum_level'  => \Rollbar\Payload\Level::ERROR,
        ]);

        $config  = Rollbar::logger()->getConfig();
        $data    = $config->getRollbarData(\Rollbar\Payload\Level::INFO, 'some message', array());
        $payload = new \Rollbar\Payload\Payload($data, $this->getTestAccessToken());
        $config->checkIgnored($payload, 'some message', false);

        $this->assertVerboseLogContains('Occurrence\'s level is too low', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\Config::checkIgnored due the custom `filter`.
     *
     * @return void
     */
    public function testRollbarConfigCheckIgnoredFilter(): void
    {
        $filterMock = $this->getMockBuilder(FilterInterface::class)->getMock();
        $filterMock->method('shouldSend')->willReturn(true);

        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'filter'         => $filterMock,
        ]);

        $config  = Rollbar::logger()->getConfig();
        $data    = $config->getRollbarData(\Rollbar\Payload\Level::INFO, 'some message', array());
        $payload = new \Rollbar\Payload\Payload($data, $this->getTestAccessToken());
        $config->checkIgnored($payload, 'some message', false);

        $unitTest   = $this;
        $filterMock = $this->getMockBuilder(FilterInterface::class)->getMock();
        $filterMock->method('shouldSend')->willReturn(true);

        $this->assertVerboseLogContains('Custom filter result: true', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\Config::send due the custom `transmit` == false.
     *
     * @return void
     */
    public function testRollbarConfigSendTransmit(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'transmit'       => false,
        ]);

        $config  = Rollbar::logger()->getConfig();
        $encoded = new \Rollbar\Payload\EncodedPayload(array());
        $config->send($encoded, $this->getTestAccessToken());

        $this->assertVerboseLogContains(
            'Not transmitting \(transmitting disabled in configuration\)',
            LogLevel::WARNING,
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::sendBatch due the custom `transmit` == false.
     *
     * @return void
     */
    public function testRollbarConfigSendBatchTransmit(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
            'transmit'       => false,
            'batched'        => true,
        ]);

        $config = Rollbar::logger()->getConfig();
        $config->sendBatch(array(), $this->getTestAccessToken());

        $this->assertVerboseLogContains(
            'Not transmitting \(transmitting disabled in configuration\)',
            LogLevel::WARNING,
        );
    }

    /**
     * Test verbosity of \Rollbar\Config::handleResponse with custom `responseHandler`.
     *
     * @return void
     */
    public function testRollbarConfigHandleResponse(): void
    {
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)->getMock();
        Rollbar::init([
            'access_token'    => $this->getTestAccessToken(),
            'environment'     => 'testing-php',
            'verbose_logger'  => $this->verboseLogger,
            'responseHandler' => $responseHandlerMock,
        ]);

        $config       = Rollbar::logger()->getConfig();
        $payloadMock  = $this->getMockBuilder(Payload::class)->disableOriginalConstructor()->getMock();
        $responseMock = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $config->handleResponse($payloadMock, $responseMock);

        $this->assertVerboseLogContains('Applying custom response handler:', LogLevel::DEBUG);
    }

    /**
     * Test verbosity of \Rollbar\Truncation\Truncation::registerStrategy in truncate method.
     *
     * @return void
     */
    public function testRollbarTruncation(): void
    {
        Rollbar::init([
            'access_token'   => $this->getTestAccessToken(),
            'environment'    => 'testing-php',
            'verbose_logger' => $this->verboseLogger,
        ]);
        Rollbar::logger()->log(
            \Rollbar\Payload\Level::INFO,
            \str_repeat("x", \Rollbar\Truncation\Truncation::MAX_PAYLOAD_SIZE),
        );

        $this->assertVerboseLogContains('Applying truncation strategy', LogLevel::DEBUG);
    }

    /**
     * Asserts that the {@see $this->verboseLogger} contains the given message at the given level.
     *
     * @param string $messageRegEx The message regular expression to match.
     * @param string $level        The level to match.
     *
     * @return void
     */
    private function assertVerboseLogContains(string $messageRegEx, string $level): void
    {
        self::assertGreaterThanOrEqual(
            0,
            $this->verboseLogger->indexOfRegex($level, $messageRegEx),
            'Verbose log does not contain expected message: "' . $messageRegEx . '" at level "' . $level . '"',
        );
    }

    /**
     * Asserts that the {@see $this->verboseLogger} contains the given logs in the given order, and that they are
     * consecutive.
     *
     * This function loops over all the logs until it finds a match for the first constraint. Then it checks the next
     * log messages to see if they match the next constraint, and so on, until it reaches the end of the constraints or
     * the end of the logs.
     *
     * If every constraint is matched in order, the test passes.
     *
     * @param array{level: string, messageRegEx: string} ...$constraints The constraints to match. The array must
     *                                                                   contain two keys: `level` and `messageRegEx`.
     *                                                                   The `level` key must contain the log level to
     *                                                                   match, and the `messageRegEx` key must contain
     *                                                                   the regular expression to match the log
     *                                                                   message.
     *
     * @return void
     */
    private function assertVerboseLogsConsecutive(array ...$constraints): void
    {
        $matchCount      = 0;
        $constraintIndex = 0;
        $consecutive     = false;
        for ($i = 0; $i < count($this->verboseLogger->logs); $i++) {
            // If this is past the last constraint, we're done.
            if ($constraintIndex >= count($constraints)) {
                break;
            }
            $message = $constraints[$constraintIndex]['messageRegEx'];
            $level   = $constraints[$constraintIndex]['level'];
            $matches    = $this->verboseLogger->indexMatchesRegex($i, $level, $message);
            if ($consecutive && !$matches) {
                // If we are expecting consecutive matches, and the current log message does not match the current
                // constraint, then we can fail early.
                $this->fail(
                    'Expected log message at index "' . $i . '" to match "' . $message . '" at level "' . $level . '"'
                );
            }
            if ($matches) {
                // Since we have found a match for the current constraint, we can now expect all future matches to be
                // consecutive.
                $consecutive = true;
                $matchCount++;
                $constraintIndex++;
            }
        }
        self::assertSame(
            count($constraints),
            $matchCount,
            'Expected ' . count($constraints) . ' log messages, but found ' . $matchCount,
        );
    }
}
