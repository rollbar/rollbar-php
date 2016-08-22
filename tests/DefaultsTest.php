<?php namespace Rollbar;

use Rollbar\Defaults;
use Rollbar\Payload\Level;
use Rollbar\Payload\Notifier;
use Psr\Log\LogLevel;

class DefaultsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Defaults
     */
    private $d;

    public function setUp()
    {
        $this->d = new Defaults();
    }

    public function testGet()
    {
        $d = Defaults::get();
        $this->assertInstanceOf("Rollbar\Defaults", $d);
    }

    public function testMessageLevel()
    {
        $this->assertEquals("warning", $this->d->messageLevel());
        $this->assertEquals("error", $this->d->messageLevel(Level::ERROR()));
    }

    public function testExceptionLevel()
    {
        $this->assertEquals("error", $this->d->exceptionLevel());
        $this->assertEquals("warning", $this->d->exceptionLevel(Level::Warning()));
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
        $this->assertEquals($expected, $this->d->errorLevels());
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
        $this->assertEquals($expected, $this->d->psrLevels());
    }

    public function testGitHash()
    {
        $val = exec('git rev-parse --verify HEAD');
        $this->assertEquals($val, $this->d->gitHash());
    }

    public function testGitBranch()
    {
        $val = exec('git rev-parse --abbrev-ref HEAD');
        $this->assertEquals($val, $this->d->gitBranch());
    }

    public function testServerRoot()
    {
        $_ENV["HEROKU_APP_DIR"] = "abc123";
        $d = new Defaults();
        $this->assertEquals("abc123", $d->serverRoot());
    }

    public function testPlatform()
    {
        $this->assertEquals(php_uname('a'), $this->d->platform());
    }

    public function testNotifier()
    {
        $this->assertEquals(Notifier::defaultNotifier(), $this->d->notifier());
    }

    public function testBaseException()
    {
        if (version_compare(phpversion(), '7.0', '<')) {
            $expected = "\Exception";
        } else {
            $expected = "\Throwable";
        }
        $base = $this->d->baseException();
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
        $this->assertEquals($expected, $this->d->scrubFields());
    }
}
