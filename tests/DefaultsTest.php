<?php namespace Rollbar;

use Rollbar\Defaults;
use Rollbar\Payload\Level;
use Rollbar\Payload\Notifier;
use Psr\Log\LogLevel;

class DefaultsTest extends BaseRollbarTest
{
    /**
     * @var Defaults
     */
    private $defaults;

    public function setUp()
    {
        $this->defaults = new Defaults(new Utilities());
    }

    public function testGet()
    {
        $defaults = Defaults::get();
        $this->assertInstanceOf("Rollbar\Defaults", $defaults);
    }

    public function testMessageLevel()
    {
        $this->assertEquals("warning", $this->defaults->messageLevel());
        $this->assertEquals("error", $this->defaults->messageLevel(Level::ERROR));
    }

    public function testExceptionLevel()
    {
        $this->assertEquals("error", $this->defaults->exceptionLevel());
        $this->assertEquals("warning", $this->defaults->exceptionLevel(Level::WARNING));
    }

    public function testErrorLevels()
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

    public function testPsrLevels()
    {
        $expected = $this->defaultPsrLevels = array(
            LogLevel::EMERGENCY => "critical",
            "emergency" => "critical",
            LogLevel::ALERT => "critical",
            "alert" => "critical",
            LogLevel::CRITICAL => "critical",
            "critical" => "critical",
            LogLevel::ERROR => "error",
            "error" => "error",
            LogLevel::WARNING => "warning",
            "warning" => "warning",
            LogLevel::NOTICE => "info",
            "notice" => "info",
            LogLevel::INFO => "info",
            "info" => "info",
            LogLevel::DEBUG => "debug",
            "debug" => "debug"
        );
        $this->assertEquals($expected, $this->defaults->psrLevels());
    }

    public function testBranch()
    {
        $val = 'some-branch';
        $this->assertEquals($val, $this->defaults->branch($val));
    }

    public function testServerRoot()
    {
        $_ENV["HEROKU_APP_DIR"] = "abc123";
        $defaults = new Defaults(new Utilities);
        $this->assertEquals("abc123", $defaults->serverRoot());
    }

    public function testPlatform()
    {
        $this->assertEquals(php_uname('a'), $this->defaults->platform());
    }

    public function testNotifier()
    {
        $this->assertEquals(Notifier::defaultNotifier(), $this->defaults->notifier());
    }

    public function testBaseException()
    {
        if (version_compare(phpversion(), '7.0', '<')) {
            $expected = "\Exception";
        } else {
            $expected = "\Throwable";
        }
        $base = $this->defaults->baseException();
        $this->assertEquals($expected, $base);
    }

    public function testScrubFields()
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
    
    public function testSendMessageTrace()
    {
        $this->assertFalse($this->defaults->sendMessageTrace());
    }
    
    public function testAgentLogLocation()
    {
        $this->assertEquals('/var/tmp', $this->defaults->agentLogLocation());
    }
    
    public function testAllowExec()
    {
        $this->assertEquals(true, $this->defaults->allowExec());
    }
    
    public function testEndpoint()
    {
        $this->assertEquals('https://api.rollbar.com/api/1/', $this->defaults->endpoint());
    }
    
    public function testCaptureErrorStacktraces()
    {
        $this->assertTrue($this->defaults->captureErrorStacktraces());
    }
    
    public function testCheckIgnore()
    {
        $this->assertNull($this->defaults->checkIgnore());
    }
    
    public function testCodeVersion()
    {
        $this->assertEquals("", $this->defaults->codeVersion());
    }
    
    public function testCustom()
    {
        $this->assertNull($this->defaults->custom());
    }
    
    public function testEnabled()
    {
        $this->assertTrue($this->defaults->enabled());
    }

    public function testTransmit()
    {
        $this->assertTrue($this->defaults->transmit());
    }

    public function testLogPayload()
    {
        $this->assertFalse($this->defaults->logPayload());
    }
    
    public function testEnvironment()
    {
        $this->assertEquals('production', $this->defaults->environment());
    }
    
    public function testErrorSampleRates()
    {
        $this->assertEmpty($this->defaults->errorSampleRates());
    }
    
    public function testExceptionSampleRates()
    {
        $this->assertEmpty($this->defaults->exceptionSampleRates());
    }
    
    public function testFluentHost()
    {
        $this->assertEquals('127.0.0.1', $this->defaults->fluentHost());
    }
    
    public function testFluentPort()
    {
        $this->assertEquals(24224, $this->defaults->fluentPort());
    }
    
    public function testFluentTag()
    {
        $this->assertEquals('rollbar', $this->defaults->fluentTag());
    }
    
    public function testHandler()
    {
        $this->assertEquals('blocking', $this->defaults->handler());
    }
    
    public function testHost()
    {
        $this->assertNull($this->defaults->host());
    }
    
    public function testIncludedErrno()
    {
        $this->assertEquals(
            ROLLBAR_INCLUDED_ERRNO_BITMASK,
            $this->defaults->includedErrno()
        );
    }
    
    public function testTimeout()
    {
        $this->assertEquals(3, $this->defaults->timeout());
    }
    
    public function testReportSuppressed()
    {
        $this->assertFalse($this->defaults->reportSuppressed());
    }
    
    public function testUseErrorReporting()
    {
        $this->assertFalse($this->defaults->useErrorReporting());
    }
    
    public function testCaptureEmail()
    {
        $this->assertFalse($this->defaults->captureEmail());
    }
    
    public function testCaptureUsername()
    {
        $this->assertFalse($this->defaults->captureUsername());
    }
    
    public function testMaxItems()
    {
        $this->assertEquals(10, $this->defaults->maxItems());
    }
    
    public function testRaiseOnError()
    {
        $this->assertEquals(false, $this->defaults->raiseOnError());
    }
    
    public function testDefaultsForConfigOptions()
    {
        foreach (\Rollbar\Config::listOptions() as $option) {
            if ($option == 'access_token' ||
                $option == 'logger' ||
                $option == 'person' ||
                $option == 'person_fn' ||
                $option == 'scrub_whitelist' ||
                $option == 'proxy' ||
                $option == 'include_raw_request_body' ||
                $option == 'verbose_logger' ||
                $option == 'log_payload_logger') {
                continue;
            } elseif ($option == 'base_api_url') {
                $option = 'endpoint';
            } elseif ($option == 'capture_ip') {
                $option = 'captureIP';
            } elseif ($option == 'root') {
                $option = 'server_root';
            }
            
            $this->defaults->fromSnakeCase($option);
        }
    }
    
    public function testFromSnakeCase()
    {
        $this->assertEquals(
            'warning',
            \Rollbar\Defaults::get()->fromSnakeCase('message_level')
        );
    }
}
