<?php namespace Rollbar;

use Rollbar\Utilities;
use Rollbar\Payload\Notifier;
use Psr\Log\LogLevel;

class Defaults
{
    private static $singleton = null;

    public static function get()
    {
        if (is_null(self::$singleton)) {
            self::$singleton = new Defaults();
        }
        return self::$singleton;
    }

    private static function getGitHash()
    {
        try {
            @exec('git rev-parse --verify HEAD 2> /dev/null', $output);
            return @$output[0];
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function getGitBranch()
    {
        try {
            @exec('git rev-parse --abbrev-ref HEAD 2> /dev/null', $output);
            return @$output[0];
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function getServerRoot()
    {
        return isset($_ENV["HEROKU_APP_DIR"]) ? $_ENV["HEROKU_APP_DIR"] : null;
    }

    private static function getPlatform()
    {
        return php_uname('a');
    }

    private static function getNotifier()
    {
        return Notifier::defaultNotifier();
    }

    private static function getBaseException()
    {
        return version_compare(phpversion(), '7.0', '<')
            ? '\Exception'
            : '\Throwable';
    }

    private static function getScrubFields()
    {
        return array(
            'passwd',
            'password',
            'secret',
            'confirm_password',
            'password_confirmation',
            'auth_token',
            'csrf_token',
            'access_token'
        );
    }
    
    public function sendMessageTrace($sendMessageTrace = null)
    {
        return $sendMessageTrace ? $sendMessageTrace : $this->defaultSendMessageTrace;
    }

    private $defaultMessageLevel = "warning";
    private $defaultExceptionLevel = "error";
    private $defaultPsrLevels;
    private $defaultCodeVersion;
    private $defaultErrorLevels;
    private $defaultGitHash;
    private $defaultGitBranch;
    private $defaultServerRoot;
    private $defaultPlatform;
    private $defaultNotifier;
    private $defaultBaseException;
    private $defaultScrubFields;
    private $defaultSendMessageTrace;
    private $defaultIncludeCodeContext;
    private $defaultExceptionIncludeCodeContext;

    public function __construct()
    {
        $this->defaultPsrLevels = array(
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
        $this->defaultErrorLevels = array(
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
        $this->defaultGitHash = self::getGitHash();
        $this->defaultGitBranch = self::getGitBranch();
        $this->defaultServerRoot = self::getServerRoot();
        $this->defaultPlatform = self::getPlatform();
        $this->defaultNotifier = self::getNotifier();
        $this->defaultBaseException = self::getBaseException();
        $this->defaultScrubFields = self::getScrubFields();
        $this->defaultCodeVersion = "";
        $this->defaultSendMessageTrace = false;
        $this->defaultIncudeCodeContext = false;
        $this->defaultIncludeExceptionCodeContext = false;
    }

    public function messageLevel($level = null)
    {
        return Utilities::coalesce($level, $this->defaultMessageLevel);
    }

    public function exceptionLevel($level = null)
    {
        return Utilities::coalesce($level, $this->defaultExceptionLevel);
    }

    public function errorLevels($level = null)
    {
        return Utilities::coalesce($level, $this->defaultErrorLevels);
    }

    public function psrLevels($level = null)
    {
        return Utilities::coalesce($level, $this->defaultPsrLevels);
    }

    public function codeVersion($codeVersion = null)
    {
        return Utilities::coalesce($codeVersion, $this->defaultCodeVersion);
    }

    public function gitHash($gitHash = null)
    {
        return Utilities::coalesce($gitHash, $this->defaultGitHash);
    }

    public function gitBranch($gitBranch = null)
    {
        return Utilities::coalesce($gitBranch, $this->defaultGitBranch);
    }

    public function serverRoot($serverRoot = null)
    {
        return Utilities::coalesce($serverRoot, $this->defaultServerRoot);
    }

    public function platform($platform = null)
    {
        return Utilities::coalesce($platform, $this->defaultPlatform);
    }

    public function notifier($notifier = null)
    {
        return Utilities::coalesce($notifier, $this->defaultNotifier);
    }

    public function baseException($baseException = null)
    {
        return Utilities::coalesce($baseException, $this->defaultBaseException);
    }

    public function scrubFields($scrubFields = null)
    {
        return Utilities::coalesce($scrubFields, $this->defaultScrubFields);
    }

    public function includeCodeContext($includeCodeContext = null)
    {
        return Utilities::coalesce($includeCodeContext, $this->defaultIncludeCodeContext);
    }

    public function includeExceptionCodeContext($includeExceptionCodeContext = null)
    {
        return Utilities::coalesce($includeExceptionCodeContext, $this->defaultIncludeExceptionCodeContext);
    }
}
