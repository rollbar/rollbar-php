<?php declare(strict_types=1);

namespace Rollbar;

use Monolog\Logger;
use Rollbar\Payload\Notifier;
use Psr\Log\LogLevel;
use Throwable;

class Defaults
{

    public static function get()
    {
        if (is_null(self::$singleton)) {
            self::$singleton = new Defaults;
        }
        return self::$singleton;
    }

    public function __construct()
    {
        $this->psrLevels = array(
            LogLevel::EMERGENCY => "critical",
            LogLevel::ALERT => "critical",
            LogLevel::CRITICAL => "critical",
            LogLevel::ERROR => "error",
            LogLevel::WARNING => "warning",
            LogLevel::NOTICE => "info",
            LogLevel::INFO => "info",
            LogLevel::DEBUG => "debug",
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
        $this->serverRoot = $_ENV["HEROKU_APP_DIR"] ?? null;
        $this->platform = php_uname('a');
        $this->notifier = Notifier::defaultNotifier();
        $this->baseException = Throwable::class;
        $this->errorSampleRates = array();
        $this->exceptionSampleRates = array();

        if (defined('ROLLBAR_INCLUDED_ERRNO_BITMASK')) {
            $this->includedErrno = ROLLBAR_INCLUDED_ERRNO_BITMASK;
        }
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
    
    private $data;
    private static $singleton = null;
    
    public function psrLevels($value = null)
    {
        return $value ?? $this->psrLevels;
    }

    public function errorLevels($value = null)
    {
        return $value ?? $this->errorLevels;
    }

    public function autodetectBranch($value = null)
    {
        return $value ?? $this->autodetectBranch;
    }

    public function branch($value = null)
    {
        return $value ?? $this->branch;
    }

    public function serverRoot($value = null)
    {
        return $value ?? $this->serverRoot;
    }

    public function platform($value = null)
    {
        return $value ?? $this->platform;
    }

    public function notifier($value = null)
    {
        return $value ?? $this->notifier;
    }

    public function baseException($value = null)
    {
        return $value ?? $this->baseException;
    }

    public function codeVersion($value = null)
    {
        return $value ?? $this->codeVersion;
    }

    public function sendMessageTrace($value = null)
    {
        return $value ?? $this->sendMessageTrace;
    }

    public function includeCodeContext($value = null)
    {
        return $value ?? $this->includeCodeContext;
    }

    public function includeExcCodeContext($value = null)
    {
        return $value ?? $this->includeExcCodeContext;
    }

    public function rawRequestBody($value = null)
    {
        return $value ?? $this->rawRequestBody;
    }

    public function localVarsDump($value = null)
    {
        return $value ?? $this->localVarsDump;
    }

    public function errorSampleRates($value = null)
    {
        return $value ?? $this->errorSampleRates;
    }

    public function exceptionSampleRates($value = null)
    {
        return $value ?? $this->exceptionSampleRates;
    }

    public function includedErrno($value = null)
    {
        return $value ?? $this->includedErrno;
    }

    public function includeErrorCodeContext($value = null)
    {
        return $value ?? $this->includeErrorCodeContext;
    }

    public function includeExceptionCodeContext($value = null)
    {
        return $value ?? $this->includeExceptionCodeContext;
    }

    public function agentLogLocation($value = null)
    {
        return $value ?? $this->agentLogLocation;
    }

    public function allowExec($value = null)
    {
        return $value ?? $this->allowExec;
    }

    public function messageLevel($value = null)
    {
        return $value ?? $this->messageLevel;
    }

    public function exceptionLevel($value = null)
    {
        return $value ?? $this->exceptionLevel;
    }

    public function endpoint($value = null)
    {
        return $value ?? $this->endpoint;
    }

    public function captureErrorStacktraces($value = null)
    {
        return $value ?? $this->captureErrorStacktraces;
    }

    public function checkIgnore($value = null)
    {
        return $value ?? $this->checkIgnore;
    }

    public function custom($value = null)
    {
        return $value ?? $this->custom;
    }

    public function customDataMethod($value = null)
    {
        return $value ?? $this->customDataMethod;
    }

    public function enabled($value = null)
    {
        return $value ?? $this->enabled;
    }

    public function transmit($value = null)
    {
        return $value ?? $this->transmit;
    }

    public function logPayload($value = null)
    {
        return $value ?? $this->logPayload;
    }

    public function verbose($value = null)
    {
        return $value ?? $this->verbose;
    }

    public function environment($value = null)
    {
        return $value ?? $this->environment;
    }

    public function fluentHost($value = null)
    {
        return $value ?? $this->fluentHost;
    }

    public function fluentPort($value = null)
    {
        return $value ?? $this->fluentPort;
    }

    public function fluentTag($value = null)
    {
        return $value ?? $this->fluentTag;
    }

    public function handler($value = null)
    {
        return $value ?? $this->handler;
    }

    public function host($value = null)
    {
        return $value ?? $this->host;
    }

    public function timeout($value = null)
    {
        return $value ?? $this->timeout;
    }

    public function reportSuppressed($value = null)
    {
        return $value ?? $this->reportSuppressed;
    }

    public function useErrorReporting($value = null)
    {
        return $value ?? $this->useErrorReporting;
    }

    public function captureIP($value = null)
    {
        return $value ?? $this->captureIP;
    }

    public function captureEmail($value = null)
    {
        return $value ?? $this->captureEmail;
    }

    public function captureUsername($value = null)
    {
        return $value ?? $this->captureUsername;
    }

    public function scrubFields($value = null)
    {
        return $value ?? $this->scrubFields;
    }

    public function customTruncation($value = null)
    {
        return $value ?? $this->customTruncation;
    }

    public function maxNestingDepth($value = null)
    {
        return $value ?? $this->maxNestingDepth;
    }
    
    public function maxItems($value = null)
    {
        return $value ?? $this->maxItems;
    }

    public function minimumLevel($value = null)
    {
        return $value ?? $this->minimumLevel;
    }
    
    public function raiseOnError($value = null)
    {
        return $value ?? $this->raiseOnError;
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
    private $includedErrno = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
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
    private $transmit = true;
    private $logPayload = false;
    private $verbose = \Rollbar\Config::VERBOSE_NONE;
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
    private $minimumLevel = 0;
    private $raiseOnError = false;
}
