<?php namespace Rollbar;

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

    private $messageLevel = "warning";
    private $exceptionLevel = "error";
    private $psrLevels;
    private $errorLevels;
    private $gitHash;
    private $gitBranch;
    private $serverRoot;
    private $platform;
    private $notifier;
    private $baseException;
    private $scrubFields;

    public function __construct()
    {
        $this->psrLevels = array(
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
        $this->errorLevels = array(
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
        $this->gitHash = self::getGitHash();
        $this->gitBranch = self::getGitBranch();
        $this->serverRoot = self::getServerRoot();
        $this->platform = self::getPlatform();
        $this->notifier = self::getNotifier();
        $this->baseException = self::getBaseException();
        $this->scrubFields = self::getScrubFields();
    }

    public function messageLevel($level)
    {
        return $level || $this->messageLevel;
    }

    public function exceptionLevel($level)
    {
        return $level || $this->exceptionLevel;
    }

    public function errorLevels($level)
    {
        return $level || $this->errorLevels;
    }

    public function psrLevels($level)
    {
        return $level || $this->psrLevels;
    }

    public function codeVersion($codeVersion)
    {
        return $codeVersion || $this->codeVersion;
    }

    public function gitHash($gitHash)
    {
        return $gitHash || $this->gitHash;
    }

    public function gitBranch($gitBranch)
    {
        return $gitBranch || $this->gitBranch;
    }

    public function serverRoot($serverRoot)
    {
        return $serverRoot || $this->serverRoot;
    }

    public function platform($platform)
    {
        return $platform || $this->platform;
    }

    public function notifier($notifier)
    {
        return $notifier || $this->notifier;
    }

    public function baseException($baseException)
    {
        return $baseException || $this->baseException;
    }

    public function scrubFields($scrubFields)
    {
        return $scrubFields || $this->scrubFields;
    }
}
