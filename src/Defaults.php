<?php namespace Rollbar;

use Rollbar\Utilities;
use Rollbar\Payload\Notifier;
use Psr\Log\LogLevel;

class Defaults
{

    public static function get()
    {
        if (is_null(self::$singleton)) {
            self::$singleton = new Defaults(new Utilities());
        }
        return self::$singleton;
    }

    public function __construct($utilities)
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
        $this->scrubFields = array(
            'passwd',
            'password',
            'secret',
            'confirm_password',
            'password_confirmation',
            'auth_token',
            'csrf_token',
            'access_token'
        );
        $this->serverRoot = isset($_ENV["HEROKU_APP_DIR"]) ? $_ENV["HEROKU_APP_DIR"] : null;
        $this->platform = php_uname('a');
        $this->notifier = Notifier::defaultNotifier();
        $this->baseException = version_compare(phpversion(), '7.0', '<') ? '\Exception' : '\Throwable';
        $this->errorSampleRates = array();
        $this->exceptionSampleRates = array();
        
        $this->utilities = $utilities;
    }
    
    public function fromSnakeCase($option)
    {
        $spaced = str_replace('_', ' ', $option);
        $method = lcfirst(str_replace(' ', '', ucwords($spaced)));
        
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            throw new \Exception('No default value defined for property ' . $method . '.');
        }
    }
    
    private $utilities;
    private $data;
    private static $singleton = null;
    
    public function psrLevels($value = null)
    {
        return $value !== null ? $value : $this->psrLevels;
    }

    public function errorLevels($value = null)
    {
        return $value !== null ? $value : $this->errorLevels;
    }

    public function autodetectBranch($value = null)
    {
        return $value !== null ? $value : $this->autodetectBranch;
    }

    public function branch($value = null)
    {
        return $value !== null ? $value : $this->branch;
    }

    public function serverRoot($value = null)
    {
        return $value !== null ? $value : $this->serverRoot;
    }

    public function platform($value = null)
    {
        return $value !== null ? $value : $this->platform;
    }

    public function notifier($value = null)
    {
        return $value !== null ? $value : $this->notifier;
    }

    public function baseException($value = null)
    {
        return $value !== null ? $value : $this->baseException;
    }

    public function codeVersion($value = null)
    {
        return $value !== null ? $value : $this->codeVersion;
    }

    public function sendMessageTrace($value = null)
    {
        return $value !== null ? $value : $this->sendMessageTrace;
    }

    public function includeCodeContext($value = null)
    {
        return $value !== null ? $value : $this->includeCodeContext;
    }

    public function includeExcCodeContext($value = null)
    {
        return $value !== null ? $value : $this->includeExcCodeContext;
    }

    public function rawRequestBody($value = null)
    {
        return $value !== null ? $value : $this->rawRequestBody;
    }

    public function localVarsDump($value = null)
    {
        return $value !== null ? $value : $this->localVarsDump;
    }

    public function errorSampleRates($value = null)
    {
        return $value !== null ? $value : $this->errorSampleRates;
    }

    public function exceptionSampleRates($value = null)
    {
        return $value !== null ? $value : $this->exceptionSampleRates;
    }

    public function includedErrno($value = null)
    {
        return $value !== null ? $value : $this->includedErrno;
    }

    public function includeErrorCodeContext($value = null)
    {
        return $value !== null ? $value : $this->includeErrorCodeContext;
    }

    public function includeExceptionCodeContext($value = null)
    {
        return $value !== null ? $value : $this->includeExceptionCodeContext;
    }

    public function agentLogLocation($value = null)
    {
        return $value !== null ? $value : $this->agentLogLocation;
    }

    public function allowExec($value = null)
    {
        return $value !== null ? $value : $this->allowExec;
    }

    public function messageLevel($value = null)
    {
        return $value !== null ? $value : $this->messageLevel;
    }

    public function exceptionLevel($value = null)
    {
        return $value !== null ? $value : $this->exceptionLevel;
    }

    public function endpoint($value = null)
    {
        return $value !== null ? $value : $this->endpoint;
    }

    public function captureErrorStacktraces($value = null)
    {
        return $value !== null ? $value : $this->captureErrorStacktraces;
    }

    public function checkIgnore($value = null)
    {
        return $value !== null ? $value : $this->checkIgnore;
    }

    public function custom($value = null)
    {
        return $value !== null ? $value : $this->custom;
    }

    public function customDataMethod($value = null)
    {
        return $value !== null ? $value : $this->customDataMethod;
    }

    public function enabled($value = null)
    {
        return $value !== null ? $value : $this->enabled;
    }

    public function environment($value = null)
    {
        return $value !== null ? $value : $this->environment;
    }

    public function fluentHost($value = null)
    {
        return $value !== null ? $value : $this->fluentHost;
    }

    public function fluentPort($value = null)
    {
        return $value !== null ? $value : $this->fluentPort;
    }

    public function fluentTag($value = null)
    {
        return $value !== null ? $value : $this->fluentTag;
    }

    public function handler($value = null)
    {
        return $value !== null ? $value : $this->handler;
    }

    public function host($value = null)
    {
        return $value !== null ? $value : $this->host;
    }

    public function timeout($value = null)
    {
        return $value !== null ? $value : $this->timeout;
    }

    public function reportSuppressed($value = null)
    {
        return $value !== null ? $value : $this->reportSuppressed;
    }

    public function useErrorReporting($value = null)
    {
        return $value !== null ? $value : $this->useErrorReporting;
    }

    public function captureIP($value = null)
    {
        return $value !== null ? $value : $this->captureIP;
    }

    public function captureEmail($value = null)
    {
        return $value !== null ? $value : $this->captureEmail;
    }

    public function captureUsername($value = null)
    {
        return $value !== null ? $value : $this->captureUsername;
    }

    public function scrubFields($value = null)
    {
        return $value !== null ? $value : $this->scrubFields;
    }

    public function customTruncation($value = null)
    {
        return $value !== null ? $value : $this->customTruncation;
    }

    public function maxNestingDepth($value = null)
    {
        return $value !== null ? $value : $this->maxNestingDepth;
    }
    
    public function maxItems($value = null)
    {
        return $value !== null ? $value : $this->maxItems;
    }

    private $psrLevels;
    private $errorLevels;
    private $autodetectBranch = false;
    private $branch = null;
    private $serverRoot;
    private $platform;
    private $notifier;
    private $baseException;
    private $codeVersion = "";
    private $sendMessageTrace = false;
    private $includeCodeContext = false;
    private $includeExcCodeContext = false;
    private $rawRequestBody = false;
    private $localVarsDump = true;
    private $maxNestingDepth = -1;
    private $errorSampleRates = array();
    private $exceptionSampleRates = array();
    private $includedErrno = ROLLBAR_INCLUDED_ERRNO_BITMASK;
    private $includeErrorCodeContext = null;
    private $includeExceptionCodeContext = null;
    private $agentLogLocation = '/var/tmp';
    private $allowExec = true;
    private $messageLevel = "warning";
    private $exceptionLevel = "error";
    private $endpoint = 'https://api.rollbar.com/api/1/';
    private $captureErrorStacktraces = true;
    private $checkIgnore = null;
    private $custom = null;
    private $customDataMethod = null;
    private $enabled = true;
    private $environment = 'production';
    private $fluentHost = '127.0.0.1';
    private $fluentPort = 24224;
    private $fluentTag = 'rollbar';
    private $handler = 'blocking';
    private $host = null;
    private $timeout = 3;
    private $reportSuppressed = false;
    private $useErrorReporting = false;
    private $captureIP = true;
    private $captureEmail = false;
    private $captureUsername = false;
    private $scrubFields;
    private $customTruncation = null;
    private $maxItems = 10;
}
