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
            self::$singleton = new Defaults(new Utilities());
        }
        return self::$singleton;
    }

    private static function getGitHash()
    {
        try {
            if (function_exists('shell_exec')) {
                $output = rtrim(shell_exec('git rev-parse --verify HEAD 2> /dev/null'));
                if ($output) {
                    return $output;
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function getGitBranch()
    {
        try {
            if (function_exists('shell_exec')) {
                $output = rtrim(shell_exec('git rev-parse --abbrev-ref HEAD 2> /dev/null'));
                if ($output) {
                    return $output;
                }
            }
            return null;
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

    /**
     * @SuppressWarnings(PHPMD.StaticAccess) Static access to default notifier
     * intended.
     */
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
        return $sendMessageTrace !== null ? $sendMessageTrace : $this->defaultSendMessageTrace;
    }
    
    public function captureErrorStacktraces($capture = null)
    {
        return $capture !== null ?
            $capture :
            $this->defaultCaptureErrorStacktraces;
    }
    
    public function localVarsDump($localVarsDump = null)
    {
        return $localVarsDump !== null ? $localVarsDump : $this->defaultLocalVarsDump;
    }

    public function rawRequestBody($rawRequestBody = null)
    {
        return $rawRequestBody !== null ? $rawRequestBody : $this->defaultRawRequestBody;
    }

    private $defaultAgentLogLocation = '/var/tmp';
    private $defaultAllowExec = true;
    private $defaultMessageLevel = "warning";
    private $defaultExceptionLevel = "error";
    private $defaultEndpoint = 'https://api.rollbar.com/api/1/';
    private $defaultCaptureErrorStacktraces = true;
    private $defaultCheckIgnore = null;
    private $defaultCustom = null;
    private $defaultEnabled = true;
    private $defaultEnvironment = 'production';
    private $defaultErrorSampleRates;
    private $defaultExceptionSampleRates;
    private $defaultFluentHost = '127.0.0.1';
    private $defaultFluentPort = 24224;
    private $defaultFluentTag = 'rollbar';
    private $defaultHandler = 'blocking';
    private $defaultHost = null;
    private $defaultIncludedErrno;
    private $defaultTimeout = 3;
    private $defaultReportSuppressed = false;
    private $defaultUseErrorReporting = false;
    private $defaultVerbosity = \Psr\Log\LogLevel::ERROR;
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
    private $defaultIncludeExcCodeContext;
    private $defaultRawRequestBody;
    private $defaultLocalVarsDump;
    private $utilities;

    public function __construct($utilities)
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
        $this->defaultGitHash = null;
        $this->defaultGitBranch = null;
        $this->defaultServerRoot = self::getServerRoot();
        $this->defaultPlatform = self::getPlatform();
        $this->defaultNotifier = self::getNotifier();
        $this->defaultBaseException = self::getBaseException();
        $this->defaultScrubFields = self::getScrubFields();
        $this->defaultCodeVersion = "";
        $this->defaultSendMessageTrace = false;
        $this->defaultIncludeCodeContext = false;
        $this->defaultIncludeExcCodeContext = false;
        $this->defaultRawRequestBody = false;
        $this->defaultLocalVarsDump = true;
        $this->defaultErrorSampleRates = array();
        $this->defaultExceptionSampleRates = array();
        $this->defaultIncludedErrno = ROLLBAR_INCLUDED_ERRNO_BITMASK;
        
        $this->utilities = $utilities;
    }
    
    public function agentLogLocation($agentLogLocation = null)
    {
        return $agentLogLocation ?: $this->defaultAgentLogLocation;
    }
    
    public function allowExec($allowExec = null)
    {
        return $allowExec ?: $this->defaultAllowExec;
    }
    
    public function endpoint($endpoint = null)
    {
        return $endpoint ?: $this->defaultEndpoint;
    }
    
    public function checkIgnore($checkIgnore = null)
    {
        return $checkIgnore ?: $this->defaultCheckIgnore;
    }
    
    public function custom($custom = null)
    {
        return $custom ?: $this->defaultCustom;
    }
    
    public function enabled($enabled = null)
    {
        return $enabled ?: $this->defaultEnabled;
    }
    
    public function environment($environment = null)
    {
        return $environment ?: $this->defaultEnvironment;
    }
    
    public function errorSampleRates($errorSampleRates = null)
    {
        return $errorSampleRates ?: $this->defaultErrorSampleRates;
    }
    
    public function exceptionSampleRates($exceptionSampleRates = null)
    {
        return $exceptionSampleRates ?: $this->defaultExceptionSampleRates;
    }
    
    public function fluentHost($fluentHost = null)
    {
        return $fluentHost ?: $this->defaultFluentHost;
    }
    
    public function fluentPort($fluentPort = null)
    {
        return $fluentPort ?: $this->defaultFluentPort;
    }
    
    public function fluentTag($fluentTag = null)
    {
        return $fluentTag ?: $this->defaultFluentTag;
    }
    
    public function handler($handler = null)
    {
        return $handler ?: $this->defaultHandler;
    }
    
    public function includedErrno($includedErrno = null)
    {
        return $includedErrno ?: $this->defaultIncludedErrno;
    }
    
    public function host($host = null)
    {
        return $host ?: $this->defaultHost;
    }
    
    public function timeout($timeout = null)
    {
        return $timeout ?: $this->defaultTimeout;
    }
    
    public function reportSuppressed($reportSuppressed = null)
    {
        return $reportSuppressed ?: $this->defaultReportSuppressed;
    }
    
    public function useErrorReporting($useErrorReporting = null)
    {
        return $useErrorReporting ?: $this->defaultUseErrorReporting;
    }
    
    public function verbosity($verbosity = null)
    {
        return $verbosity ?: $this->defaultVerbosity;
    }

    public function messageLevel($level = null)
    {
        return $level ?: $this->defaultMessageLevel;
    }

    public function exceptionLevel($level = null)
    {
        return $level ?: $this->defaultExceptionLevel;
    }

    public function errorLevels($level = null)
    {
        return $level ?: $this->defaultErrorLevels;
    }
    
    public function psrLevels($level = null)
    {
        return $level ?: $this->defaultPsrLevels;
    }

    public function codeVersion($codeVersion = null)
    {
        return $codeVersion ?: $this->defaultCodeVersion;
    }

    public function gitHash($gitHash = null, $allowExec = true)
    {
        if ($gitHash) {
            return $gitHash;
        }
        if (!isset($this->defaultGitHash) && $allowExec) {
            $this->defaultGitHash = self::getGitHash();
        }
        return $this->defaultGitHash;
    }

    public function gitBranch($gitBranch = null, $allowExec = true)
    {
        if ($gitBranch) {
            return $gitBranch;
        }
        if (!isset($this->defaultGitBranch) && $allowExec) {
            $this->defaultGitBranch = self::getGitBranch();
        }
        return $this->defaultGitBranch;
    }

    public function serverRoot($serverRoot = null)
    {
        return $serverRoot ?: $this->defaultServerRoot;
    }

    public function platform($platform = null)
    {
        return $platform ?: $this->defaultPlatform;
    }

    public function notifier($notifier = null)
    {
        return $notifier ?: $this->defaultNotifier;
    }

    public function baseException($baseException = null)
    {
        return $baseException ?: $this->defaultBaseException;
    }

    public function scrubFields($scrubFields = null)
    {
        return $scrubFields ?: $this->defaultScrubFields;
    }

    public function includeCodeContext($includeCodeContext = null)
    {
        return $includeCodeContext ?: $this->defaultIncludeCodeContext;
    }

    public function includeExcCodeContext($includeExcCodeContext = null)
    {
        return $includeExcCodeContext ?: $this->defaultIncludeExcCodeContext;
    }
}
