<?php namespace Rollbar;

use Exception;
use Rollbar\Defaults;
use Rollbar\Payload\Level;
use Rollbar\Payload\Notifier;
use Psr\Log\LogLevel;
use Throwable;

class DefaultsTest extends BaseRollbarTest
{
    /**
     * @var Defaults
     */
    private \Rollbar\Defaults $defaults;

    /**
     * @var string[]
     */
    private array $defaultPsrLevels;

    public function setUp(): void
    {
        $this->defaults = new Defaults;
    }

    public function testGet(): void
    {
        $defaults = Defaults::get();
        $this->assertInstanceOf(Defaults::class, $defaults);
    }

    public function testMessageLevel(): void
    {
        $this->assertEquals("warning", $this->defaults->messageLevel());
        $this->assertEquals("error", $this->defaults->messageLevel(Level::ERROR));
    }

    public function testExceptionLevel(): void
    {
        $this->assertEquals("error", $this->defaults->exceptionLevel());
        $this->assertEquals("warning", $this->defaults->exceptionLevel(Level::WARNING));
    }

    public function testErrorLevels(): void
    {
        $expected = array(
            E_ERROR => "error",
            E_WARNING => "warning",
            E_PARSE => "critical",
            E_NOTICE => "debug",
            E_CORE_ERROR => "critical",
            E_CORE_WARNING => "warning",
            E_COMPILE_ERROR => "critical",
            E_COMPILE_WARNING => "warning",
            E_USER_ERROR => "error",
            E_USER_WARNING => "warning",
            E_USER_NOTICE => "debug",
            E_STRICT => "info",
            E_RECOVERABLE_ERROR => "error",
            E_DEPRECATED => "info",
            E_USER_DEPRECATED => "info"
        );
        $this->assertEquals($expected, $this->defaults->errorLevels());
    }

    public function testPsrLevels(): void
    {
        $expected = $this->defaultPsrLevels = array(
            LogLevel::EMERGENCY => "critical",
            LogLevel::ALERT => "critical",
            LogLevel::CRITICAL => "critical",
            LogLevel::ERROR => "error",
            LogLevel::WARNING => "warning",
            LogLevel::NOTICE => "info",
            LogLevel::INFO => "info",
            LogLevel::DEBUG => "debug",
        );
        $this->assertEquals($expected, $this->defaults->psrLevels());
    }

    public function testBranch(): void
    {
        $val = 'some-branch';
        $this->assertEquals($val, $this->defaults->branch($val));
    }

    public function testServerRoot(): void
    {
        $_ENV["HEROKU_APP_DIR"] = "abc123";
        $defaults = new Defaults;
        $this->assertEquals("abc123", $defaults->serverRoot());
    }

    public function testPlatform(): void
    {
        $this->assertEquals(php_uname('a'), $this->defaults->platform());
    }

    public function testNotifier(): void
    {
        $this->assertEquals(Notifier::defaultNotifier(), $this->defaults->notifier());
    }

    public function testBaseException(): void
    {
        $expected = Throwable::class;
        $base = $this->defaults->baseException();
        $this->assertEquals($expected, $base);
    }

    public function testScrubFields(): void
    {
        $expected = array(
            'passwd',
            'password',
            'secret',
            'confirm_password',
            'password_confirmation',
            'auth_token',
            'csrf_token',
            'access_token'
        );
        $this->assertEquals($expected, $this->defaults->scrubFields());
    }
    
    public function testSendMessageTrace(): void
    {
        $this->assertFalse($this->defaults->sendMessageTrace());
    }
    
    public function testAgentLogLocation(): void
    {
        $this->assertEquals('/var/tmp', $this->defaults->agentLogLocation());
    }
    
    public function testAllowExec(): void
    {
        $this->assertEquals(true, $this->defaults->allowExec());
    }
    
    public function testEndpoint(): void
    {
        $this->assertEquals('https://api.rollbar.com/api/1/', $this->defaults->endpoint());
    }
    
    public function testCaptureErrorStacktraces(): void
    {
        $this->assertTrue($this->defaults->captureErrorStacktraces());
    }
    
    public function testCheckIgnore(): void
    {
        $this->assertNull($this->defaults->checkIgnore());
    }
    
    public function testCodeVersion(): void
    {
        $this->assertEquals("", $this->defaults->codeVersion());
    }
    
    public function testCustom(): void
    {
        $this->assertNull($this->defaults->custom());
    }
    
    public function testEnabled(): void
    {
        $this->assertTrue($this->defaults->enabled());
    }

    public function testTransmit(): void
    {
        $this->assertTrue($this->defaults->transmit());
    }

    public function testLogPayload(): void
    {
        $this->assertFalse($this->defaults->logPayload());
    }
    
    public function testEnvironment(): void
    {
        $this->assertEquals('production', $this->defaults->environment());
    }
    
    public function testErrorSampleRates(): void
    {
        $this->assertEmpty($this->defaults->errorSampleRates());
    }
    
    public function testExceptionSampleRates(): void
    {
        $this->assertEmpty($this->defaults->exceptionSampleRates());
    }
    
    public function testFluentHost(): void
    {
        $this->assertEquals('127.0.0.1', $this->defaults->fluentHost());
    }
    
    public function testFluentPort(): void
    {
        $this->assertEquals(24224, $this->defaults->fluentPort());
    }
    
    public function testFluentTag(): void
    {
        $this->assertEquals('rollbar', $this->defaults->fluentTag());
    }
    
    public function testHandler(): void
    {
        $this->assertEquals('blocking', $this->defaults->handler());
    }
    
    public function testHost(): void
    {
        $this->assertNull($this->defaults->host());
    }
    
    public function testIncludedErrnoDefault(): void
    {
        $expected = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $this->assertEquals(
            $expected,
            $this->defaults->includedErrno()
        );
    }
    
    /**
     * Test that a caller may set the errno to include in messages via the
     * `ROLLBAR_INCLUDED_ERRNO_BITMASK` define. Because we don't want to set
     * this and infect all other tests, we run this particular test in a
     * separate process.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIncludedErrnoDefineOverride(): void
    {
        // unlike other tests that use `$this->defaults`, we must make our
        // own Defaults object now, _after_ defining the bitmask: in the
        // prior case, `$this->defaults` is constructed before the define.
        define('ROLLBAR_INCLUDED_ERRNO_BITMASK', E_USER_WARNING);
        $this->assertEquals(
            E_USER_WARNING,
            (new Defaults)->includedErrno()
        );
    }

    public function testTimeout(): void
    {
        $this->assertEquals(3, $this->defaults->timeout());
    }
    
    public function testReportSuppressed(): void
    {
        $this->assertFalse($this->defaults->reportSuppressed());
    }
    
    public function testUseErrorReporting(): void
    {
        $this->assertFalse($this->defaults->useErrorReporting());
    }
    
    public function testCaptureEmail(): void
    {
        $this->assertFalse($this->defaults->captureEmail());
    }
    
    public function testCaptureUsername(): void
    {
        $this->assertFalse($this->defaults->captureUsername());
    }
    
    public function testMaxItems(): void
    {
        $this->assertEquals(10, $this->defaults->maxItems());
    }
    
    public function testRaiseOnError(): void
    {
        $this->assertEquals(false, $this->defaults->raiseOnError());
    }

    /**
     * @testWith ["message_level", "warning"]
     *           ["MESSAGE_LEVEL", "warning"]
     */
    public function testFromSnakeCaseGetsExpectedValueForValidOption($option, $value): void
    {
        $this->assertEquals(
            $value,
            \Rollbar\Defaults::get()->fromSnakeCase($option)
        );
    }

    public function testFromSnakeCaseThrowsOnInvalidOption(): void
    {
        $this->expectException(Exception::class);
        \Rollbar\Defaults::get()->fromSnakeCase('no_such_option');
    }
}
