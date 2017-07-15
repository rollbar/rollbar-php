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

    public function testGitHash()
    {
        $val = exec('git rev-parse --verify HEAD');
        $this->assertEquals($val, $this->defaults->gitHash());
    }

    public function testGitBranch()
    {
        $val = exec('git rev-parse --abbrev-ref HEAD');
        $this->assertEquals($val, $this->defaults->gitBranch());
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
}
