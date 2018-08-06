<?php namespace Rollbar;

use Rollbar\Utilities;
use Rollbar\Payload\Notifier;
use Psr\Log\LogLevel;

class Defaults
{
    private $utilities;
    private $data;
    private static $singleton = null;

    public static function get()
    {
        if (is_null(self::$singleton)) {
            self::$singleton = new Defaults(new Utilities());
        }
        return self::$singleton;
    }

    public function __construct($utilities)
    {
        $this->data = array();
        
        $this->data['psrLevels'] = array(
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
        $this->data['errorLevels'] = array(
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
        $this->data['gitHash'] = null;
        $this->data['gitBranch'] = null;
        $this->data['serverRoot'] = isset($_ENV["HEROKU_APP_DIR"]) ? $_ENV["HEROKU_APP_DIR"] : null;
        $this->data['platform'] = php_uname('a');
        $this->data['notifier'] = Notifier::defaultNotifier();
        $this->data['baseException'] = version_compare(phpversion(), '7.0', '<') ? '\Exception' : '\Throwable';
        $this->data['codeVersion'] = "";
        $this->data['sendMessageTrace'] = false;
        $this->data['includeCodeContext'] = false;
        $this->data['includeExcCodeContext'] = false;
        $this->data['rawRequestBody'] = false;
        $this->data['localVarsDump'] = true;
        $this->data['errorSampleRates'] = array();
        $this->data['exceptionSampleRates'] = array();
        $this->data['includedErrno'] = ROLLBAR_INCLUDED_ERRNO_BITMASK;
        $this->data['includeErrorCodeContext'] = null;
        $this->data['includeExceptionCodeContext'] = null;
        $this->data['agentLogLocation'] = '/var/tmp';
        $this->data['allowExec'] = true;
        $this->data['messageLevel'] = "warning";
        $this->data['exceptionLevel'] = "error";
        $this->data['endpoint'] = 'https://api.rollbar.com/api/1/';
        $this->data['captureErrorStacktraces'] = true;
        $this->data['checkIgnore'] = null;
        $this->data['custom'] = null;
        $this->data['customDataMethod'] = null;
        $this->data['enabled'] = true;
        $this->data['environment'] = 'production';
        $this->data['fluentHost'] = '127.0.0.1';
        $this->data['fluentPort'] = 24224;
        $this->data['fluentTag'] = 'rollbar';
        $this->data['handler'] = 'blocking';
        $this->data['host'] = null;
        $this->data['timeout'] = 3;
        $this->data['reportSuppressed'] = false;
        $this->data['useErrorReporting'] = false;
        $this->data['verbosity'] = \Psr\Log\LogLevel::ERROR;
        $this->data['captureIP'] = true;
        $this->data['captureEmail'] = false;
        $this->data['captureUsername'] = false;
        $this->data['scrubFields'] = array(
            'passwd',
            'password',
            'secret',
            'confirm_password',
            'password_confirmation',
            'auth_token',
            'csrf_token',
            'access_token'
        );
        $this->data['customTruncation'] = null;
        
        $this->utilities = $utilities;
    }
    
    public function __call($method, $args)
    {
        if (!array_key_exists($method, $this->data)) {
            throw new \Exception('No default value defined for property ' . $method . '.');
        }
        
        return (isset($args[0]) && $args[0] !== null) ? $args[0] : $this->data[$method];
    }
    
    public function fromSnakeCase($option)
    {
        $spaced = str_replace('_', ' ', $option);
        $method = lcfirst(str_replace(' ', '', ucwords($spaced)));
        return $this->$method();
    }

    public function gitBranch($gitBranch = null, $allowExec = true)
    {
        if ($gitBranch) {
            return $gitBranch;
        }
        if ($allowExec) {
            static $cachedValue;
            static $hasExecuted = false;
            if (!$hasExecuted) {
                $cachedValue = self::getGitBranch();
                $hasExecuted = true;
            }
            return $cachedValue;
        }
        return null;
    }
    
    private static function getGitBranch()
    {
        try {
            if (function_exists('shell_exec')) {
                $stdRedirCmd = Utilities::isWindows() ? " > NUL" : " 2> /dev/null";
                $output = rtrim(shell_exec('git rev-parse --abbrev-ref HEAD' . $stdRedirCmd));
                if ($output) {
                    return $output;
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
