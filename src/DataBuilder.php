<?php namespace Rollbar;

use Rollbar\Payload\Context;
use Rollbar\Payload\Message;
use Rollbar\Payload\Body;
use Rollbar\Payload\Level;
use Rollbar\Payload\Person;
use Rollbar\Payload\Server;
use Rollbar\Payload\Request;
use Rollbar\Payload\Data;
use Rollbar\Payload\Trace;
use Rollbar\Payload\Frame;
use Rollbar\Payload\TraceChain;
use Rollbar\Payload\ExceptionInfo;
use Rollbar\Rollbar;
use Rollbar\Exceptions\PersonFuncException;

class DataBuilder implements DataBuilderInterface
{
    const ANONYMIZE_IP = 'anonymize';
    
    protected static $defaults;

    protected $environment;
    protected $messageLevel;
    protected $exceptionLevel;
    protected $psrLevels;
    protected $errorLevels;
    protected $codeVersion;
    protected $platform;
    protected $framework;
    protected $context;
    protected $requestParams;
    protected $requestBody;
    protected $requestExtras;
    protected $host;
    protected $person;
    protected $personFunc;
    protected $serverRoot;
    protected $serverBranch;
    protected $serverCodeVersion;
    protected $serverExtras;
    protected $custom;
    protected $customDataMethod;
    protected $fingerprint;
    protected $title;
    protected $notifier;
    protected $baseException;
    protected $includeCodeContext;
    protected $includeExcCodeContext;
    protected $sendMessageTrace;
    protected $rawRequestBody;
    protected $localVarsDump;
    protected $captureErrorStacktraces;
    protected $captureIP;
    protected $captureEmail;
    protected $captureUsername;
    
    /**
     * @var LevelFactory
     */
    protected $levelFactory;
    
    /**
     * @var Utilities
     */
    protected $utilities;

    public function __construct($config)
    {
        self::$defaults = Defaults::get();
        
        $this->setUtilities($config);
        
        $this->setEnvironment($config);

        $this->setRawRequestBody($config);
        $this->setDefaultMessageLevel($config);
        $this->setDefaultExceptionLevel($config);
        $this->setDefaultPsrLevels($config);
        $this->setErrorLevels($config);
        $this->setCodeVersion($config);
        $this->setPlatform($config);
        $this->setFramework($config);
        $this->setContext($config);
        $this->setRequestParams($config);
        $this->setRequestBody($config);
        $this->setRequestExtras($config);
        $this->setHost($config);
        $this->setPerson($config);
        $this->setPersonFunc($config);
        $this->setServerRoot($config);
        $this->setServerBranch($config);
        $this->setServerCodeVersion($config);
        $this->setServerExtras($config);
        $this->setCustom($config);
        $this->setFingerprint($config);
        $this->setTitle($config);
        $this->setNotifier($config);
        $this->setBaseException($config);
        $this->setIncludeCodeContext($config);
        $this->setIncludeExcCodeContext($config);
        $this->setSendMessageTrace($config);
        $this->setLocalVarsDump($config);
        $this->setCaptureErrorStacktraces($config);
        $this->setLevelFactory($config);
        $this->setCaptureEmail($config);
        $this->setCaptureUsername($config);
        $this->setCaptureIP($config);
        $this->setCustomDataMethod($config);
    }

    protected function setCaptureIP($config)
    {
        $fromConfig = isset($config['capture_ip']) ? $config['capture_ip'] : null;
        $this->captureIP = self::$defaults->captureIP($fromConfig);
    }
    
    protected function setCaptureEmail($config)
    {
        $fromConfig = isset($config['capture_email']) ? $config['capture_email'] : null;
        $this->captureEmail = self::$defaults->captureEmail($fromConfig);
    }
    
    protected function setCaptureUsername($config)
    {
        $fromConfig = isset($config['capture_username']) ? $config['capture_username'] : null;
        $this->captureUsername = self::$defaults->captureUsername($fromConfig);
    }

    protected function setEnvironment($config)
    {
        $fromConfig = isset($config['environment']) ? $config['environment'] : self::$defaults->get()->environment();
        $this->utilities->validateString($fromConfig, "config['environment']", null, false);
        $this->environment = $fromConfig;
    }

    protected function setDefaultMessageLevel($config)
    {
        $fromConfig = isset($config['messageLevel']) ? $config['messageLevel'] : null;
        $this->messageLevel = self::$defaults->messageLevel($fromConfig);
    }

    protected function setDefaultExceptionLevel($config)
    {
        $fromConfig = isset($config['exceptionLevel']) ? $config['exceptionLevel'] : null;
        $this->exceptionLevel = self::$defaults->exceptionLevel($fromConfig);
    }

    protected function setDefaultPsrLevels($config)
    {
        $fromConfig = isset($config['psrLevels']) ? $config['psrLevels'] : null;
        $this->psrLevels = self::$defaults->psrLevels($fromConfig);
    }

    protected function setErrorLevels($config)
    {
        $fromConfig = isset($config['errorLevels']) ? $config['errorLevels'] : null;
        $this->errorLevels = self::$defaults->errorLevels($fromConfig);
    }

    protected function setSendMessageTrace($config)
    {
        $fromConfig = isset($config['send_message_trace']) ? $config['send_message_trace'] : null;
        $this->sendMessageTrace = self::$defaults->sendMessageTrace($fromConfig);
    }
    
    protected function setRawRequestBody($config)
    {
        $fromConfig = isset($config['include_raw_request_body']) ? $config['include_raw_request_body'] : null;
        $this->rawRequestBody = self::$defaults->rawRequestBody($fromConfig);
    }

    protected function setLocalVarsDump($config)
    {
        $fromConfig = isset($config['local_vars_dump']) ? $config['local_vars_dump'] : null;
        $this->localVarsDump = self::$defaults->localVarsDump($fromConfig);
    }
    
    protected function setCaptureErrorStacktraces($config)
    {
        $fromConfig = isset($config['capture_error_stacktraces']) ? $config['capture_error_stacktraces'] : null;
        $this->captureErrorStacktraces = self::$defaults->captureErrorStacktraces($fromConfig);
    }

    protected function setCodeVersion($config)
    {
        $fromConfig = isset($config['codeVersion']) ? $config['codeVersion'] : null;
        if (!isset($fromConfig)) {
            $fromConfig = isset($config['code_version']) ? $config['code_version'] : null;
        }
        $this->codeVersion = self::$defaults->codeVersion($fromConfig);
    }

    protected function setPlatform($config)
    {
        $fromConfig = isset($config['platform']) ? $config['platform'] : null;
        $this->platform = self::$defaults->platform($fromConfig);
    }

    protected function setFramework($config)
    {
        $this->framework = isset($config['framework']) ? $config['framework'] : null;
    }

    protected function setContext($config)
    {
        $this->context = isset($config['context']) ? $config['context'] : null;
    }

    protected function setRequestParams($config)
    {
        $this->requestParams = isset($config['requestParams']) ? $config['requestParams'] : null;
    }

    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function setRequestBody($config)
    {
        
        $this->requestBody = isset($config['requestBody']) ? $config['requestBody'] : null;
        
        if (!$this->requestBody && $this->rawRequestBody) {
            $this->requestBody = file_get_contents("php://input");
            if (version_compare(PHP_VERSION, '5.6.0') < 0) {
                $_SERVER['php://input'] = $this->requestBody;
            }
        }
    }

    protected function setRequestExtras($config)
    {
        $this->requestExtras = isset($config["requestExtras"]) ? $config["requestExtras"] : null;
    }

    protected function setPerson($config)
    {
        $this->person = isset($config['person']) ? $config['person'] : null;
    }

    protected function setPersonFunc($config)
    {
        $this->personFunc = isset($config['person_fn']) ? $config['person_fn'] : null;
    }

    protected function setServerRoot($config)
    {
        $fromConfig = isset($config['serverRoot']) ? $config['serverRoot'] : null;
        if (!isset($fromConfig)) {
            $fromConfig = isset($config['root']) ? $config['root'] : null;
        }
        $this->serverRoot = self::$defaults->serverRoot($fromConfig);
    }

    protected function setServerBranch($config)
    {
        $fromConfig = isset($config['serverBranch']) ? $config['serverBranch'] : null;
        if (!isset($fromConfig)) {
            $fromConfig = isset($config['branch']) ? $config['branch'] : null;
        }
            
        $this->serverBranch = self::$defaults->branch($fromConfig);
        
        if ($this->serverBranch === null) {
            $autodetectBranch = isset($config['autodetect_branch']) ?
                $config['autodetect_branch'] :
                self::$defaults->autodetectBranch();
            
            if ($autodetectBranch) {
                $allowExec = isset($config['allow_exec']) ?
                    $config['allow_exec'] :
                    self::$defaults->allowExec();
                    
                $this->serverBranch = $this->detectGitBranch($allowExec);
            }
        }
    }

    protected function setServerCodeVersion($config)
    {
        $this->serverCodeVersion = isset($config['serverCodeVersion']) ? $config['serverCodeVersion'] : null;
    }

    protected function setServerExtras($config)
    {
        $this->serverExtras = isset($config['serverExtras']) ? $config['serverExtras'] : null;
    }

    public function setCustom($config)
    {
        $this->custom = isset($config['custom']) ? $config['custom'] : \Rollbar\Defaults::get()->custom();
    }
    
    public function setCustomDataMethod($config)
    {
        $this->customDataMethod = isset($config['custom_data_method']) ?
            $config['custom_data_method'] :
            \Rollbar\Defaults::get()->customDataMethod();
    }

    protected function setFingerprint($config)
    {
        $this->fingerprint = isset($config['fingerprint']) ? $config['fingerprint'] : null;
    }

    protected function setTitle($config)
    {
        $this->title = isset($config['title']) ? $config['title'] : null;
    }

    protected function setNotifier($config)
    {
        $fromConfig = isset($config['notifier']) ? $config['notifier'] : null;
        $this->notifier = self::$defaults->notifier($fromConfig);
    }

    protected function setBaseException($config)
    {
        $fromConfig = isset($config['baseException']) ? $config['baseException'] : null;
        $this->baseException = self::$defaults->baseException($fromConfig);
    }

    protected function setIncludeCodeContext($config)
    {
        $fromConfig = isset($config['include_error_code_context']) ? $config['include_error_code_context'] : null;
        $this->includeCodeContext = self::$defaults->includeCodeContext($fromConfig);
    }

    protected function setIncludeExcCodeContext($config)
    {
        $fromConfig =
            isset($config['include_exception_code_context']) ? $config['include_exception_code_context'] : null;
        $this->includeExcCodeContext = self::$defaults->includeExcCodeContext($fromConfig);
    }
    
    protected function setLevelFactory($config)
    {
        $this->levelFactory = isset($config['levelFactory']) ? $config['levelFactory'] : null;
        if (!$this->levelFactory) {
            throw new \InvalidArgumentException(
                'Missing dependency: LevelFactory not provided to the DataBuilder.'
            );
        }
    }
    
    protected function setUtilities($config)
    {
        $this->utilities = isset($config['utilities']) ? $config['utilities'] : null;
        if (!$this->utilities) {
            throw new \InvalidArgumentException(
                'Missing dependency: Utilities not provided to the DataBuilder.'
            );
        }
    }

    protected function setHost($config)
    {
        $this->host = isset($config['host']) ? $config['host'] : self::$defaults->host();
    }

    /**
     * @param string $level
     * @param \Exception | \Throwable | string $toLog
     * @param $context
     * @return Data
     */
    public function makeData($level, $toLog, $context)
    {
        $env = $this->getEnvironment();
        $body = $this->getBody($toLog, $context);
        $data = new Data($env, $body);
        $data->setLevel($this->getLevel($level, $toLog))
            ->setTimestamp($this->getTimestamp())
            ->setCodeVersion($this->getCodeVersion())
            ->setPlatform($this->getPlatform())
            ->setLanguage($this->getLanguage())
            ->setFramework($this->getFramework())
            ->setContext($this->getContext())
            ->setRequest($this->getRequest())
            ->setPerson($this->getPerson())
            ->setServer($this->getServer())
            ->setCustom($this->getCustomForPayload($toLog, $context))
            ->setFingerprint($this->getFingerprint($context))
            ->setTitle($this->getTitle())
            ->setUuid($this->getUuid())
            ->setNotifier($this->getNotifier());
        return $data;
    }

    protected function getEnvironment()
    {
        return $this->environment;
    }

    protected function getBody($toLog, $context)
    {
        $baseException = $this->getBaseException();
        if ($toLog instanceof ErrorWrapper) {
            $content = $this->getErrorTrace($toLog);
        } elseif ($toLog instanceof $baseException) {
            $content = $this->getExceptionTrace($toLog);
        } else {
            $content = $this->getMessage($toLog);
        }
        return new Body($content, $context);
    }

    public function getErrorTrace(ErrorWrapper $error)
    {
        return $this->makeTrace($error, $this->includeCodeContext, $error->getClassName());
    }

    /**
     * @param \Throwable|\Exception $exc
     * @return Trace|TraceChain
     */
    public function getExceptionTrace($exc)
    {
        $chain = array();
        $chain[] = $this->makeTrace($exc, $this->includeExcCodeContext);

        $previous = $exc->getPrevious();

        $baseException = $this->getBaseException();
        while ($previous instanceof $baseException) {
            $chain[] = $this->makeTrace($previous, $this->includeExcCodeContext);
            if ($previous->getPrevious() === $previous) {
                break;
            }
            $previous = $previous->getPrevious();
        }

        if (count($chain) > 1) {
            return new TraceChain($chain);
        }

        return $chain[0];
    }

    /**
     * @param \Throwable|\Exception $exception
     * @param Boolean $includeContext whether or not to include context
     * @param string $classOverride
     * @return Trace
     */
    public function makeTrace($exception, $includeContext, $classOverride = null)
    {
        if ($this->captureErrorStacktraces) {
            $frames = $this->makeFrames($exception, $includeContext);
        } else {
            $frames = array();
        }
        
        $excInfo = new ExceptionInfo(
            $classOverride ?: get_class($exception),
            $exception->getMessage()
        );
        return new Trace($frames, $excInfo);
    }

    public function makeFrames($exception, $includeContext)
    {
        $frames = array();
        
        foreach ($this->getTrace($exception) as $frameInfo) {
            $filename = isset($frameInfo['file']) ? $frameInfo['file'] : null;
            $lineno = isset($frameInfo['line']) ? $frameInfo['line'] : null;
            $method = isset($frameInfo['function']) ? $frameInfo['function'] : null;
            if (isset($frameInfo['class'])) {
                $method = $frameInfo['class'] . "::" . $method;
            }
            $args = isset($frameInfo['args']) ? $frameInfo['args'] : null;

            $frame = new Frame($filename);
            $frame->setLineno($lineno)
                ->setMethod($method);
                
            if ($this->localVarsDump && $args !== null) {
                $frame->setArgs($args);
            }

            if ($includeContext) {
                $this->addCodeContextToFrame($frame, $filename, $lineno);
            }

            $frames[] = $frame;
        }
        
        $frames = array_reverse($frames);

        return $frames;
    }

    private function addCodeContextToFrame(Frame $frame, $filename, $line)
    {
        if (!file_exists($filename)) {
            return;
        }

        $source = $this->getSourceLines($filename);

        $total = count($source);
        $line = max($line - 1, 0);
        $frame->setCode($source[$line]);
        $offset = 6;
        $min = max($line - $offset, 0);
        $pre = null;
        $post = null;
        if ($min !== $line) {
            $pre = array_slice($source, $min, $line - $min);
        }
        $max = min($line + $offset, $total);
        if ($max !== $line) {
            $post = array_slice($source, $line + 1, $max - $line);
        }
        $frame->setContext(new Context($pre, $post));
    }

    private function getTrace($exc)
    {
        if ($exc instanceof ErrorWrapper) {
            return $exc->getBacktrace();
        } else {
            $trace = $exc->getTrace();
            
            // Add the Exception's file and line as the last frame of the trace
            array_unshift($trace, array('file' => $exc->getFile(), 'line' => $exc->getLine()));
            
            return $trace;
        }
    }

    protected function getMessage($toLog)
    {
        return new Message(
            (string)$toLog,
            $this->sendMessageTrace ?
            debug_backtrace($this->localVarsDump ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS) :
            null
        );
    }

    protected function getLevel($level, $toLog)
    {
        if (is_null($level)) {
            if ($toLog instanceof ErrorWrapper) {
                $level = isset($this->errorLevels[$toLog->errorLevel]) ? $this->errorLevels[$toLog->errorLevel] : null;
            } elseif ($toLog instanceof \Exception) {
                $level = $this->exceptionLevel;
            } else {
                $level = $this->messageLevel;
            }
        }
        $level = strtolower($level);
        $level = isset($this->psrLevels[$level]) ? $this->psrLevels[$level] : null;
        return $this->levelFactory->fromName($level);
    }

    protected function getTimestamp()
    {
        return time();
    }

    protected function getCodeVersion()
    {
        return $this->codeVersion;
    }

    protected function getPlatform()
    {
        return $this->platform;
    }

    protected function getLanguage()
    {
        return "php";
        // TODO: once the backend understands a more informative language value
        // return "PHP " . phpversion();
    }

    protected function getFramework()
    {
        return $this->framework;
    }

    protected function getContext()
    {
        return $this->context;
    }

    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getRequest()
    {
        $request = new Request();

        $request->setUrl($this->getUrl())
            ->setHeaders($this->getHeaders())
            ->setParams($this->getRequestParams())
            ->setBody($this->getRequestBody())
            ->setUserIp($this->getUserIp());
      
        if (isset($_SERVER)) {
            $request->setMethod(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null)
                ->setQueryString(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null);
        }
      
        if (isset($_GET)) {
            $request->setGet($_GET);
        }
        if (isset($_POST)) {
            $request->setPost($_POST);
        }
        
        if ($request->getMethod() === 'PUT') {
            $postData = array();
            parse_str($request->getBody(), $postData);
            $request->setPost($postData);
        }
        
        $extras = $this->getRequestExtras();
        if (!$extras) {
            $extras = array();
        }

        $request->setExtras($extras);
        
        if (isset($_SESSION) && is_array($_SESSION) && count($_SESSION) > 0) {
            $request->setSession($_SESSION);
        }
        return $request;
    }
    
    public function parseForwardedString($forwarded)
    {
        $result = array();
        
        // Remove Forwarded   = 1#forwarded-element header prefix
        $parts = trim(str_replace('Forwarded:', '', $forwarded));
        
        /**
         * Break up the forwarded-element =
         *  [ forwarded-pair ] *( ";" [ forwarded-pair ] )
         */
        $parts = explode(';', $parts);
        
        /**
         * Parse forwarded pairs
         */
        foreach ($parts as $forwardedPair) {
            $forwardedPair = trim($forwardedPair);
            
            
            if (stripos($forwardedPair, 'host=') !== false) {
                // Parse 'host' forwarded pair
                $result['host'] = substr($forwardedPair, strlen('host='));
            } elseif (stripos($forwardedPair, 'proto=') !== false) {
                // Parse 'proto' forwarded pair
                $result['proto'] = substr($forwardedPair, strlen('proto='));
            } else {
                // Parse 'for' and 'by' forwarded pairs which are comma separated
                $fpParts = explode(',', $forwardedPair);
                foreach ($fpParts as $fpPart) {
                    $fpPart = trim($fpPart);
                    
                    if (stripos($fpPart, 'for=') !== false) {
                        // Parse 'for' forwarded pair
                        $result['for'] = isset($result['for']) ? $result['for'] : array();
                        $result['for'][] = substr($fpPart, strlen('for='));
                    } elseif (stripos($fpPart, 'by=') !== false) {
                        // Parse 'by' forwarded pair
                        $result['by'] = isset($result['by']) ? $result['by'] : array();
                        $result['by'][] = substr($fpPart, strlen('by='));
                    }
                }
            }
        }
        
        return $result;
    }
    
    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getUrlProto()
    {
        $proto = '';
        
        if (!empty($_SERVER['HTTP_FORWARDED'])) {
            extract($this->parseForwardedString($_SERVER['HTTP_FORWARDED']));
        }
        
        if (empty($proto)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $proto = explode(',', strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']));
                $proto = $proto[0];
            } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $proto = 'https';
            } else {
                $proto = 'http';
            }
        }
        
        return $proto;
    }
    
    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getUrlHost()
    {
        $host = '';
        
        if (!empty($_SERVER['HTTP_FORWARDED'])) {
            extract($this->parseForwardedString($_SERVER['HTTP_FORWARDED']));
        }
        
        if (empty($host)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
            } elseif (!empty($_SERVER['HTTP_HOST'])) {
                $parts = explode(':', $_SERVER['HTTP_HOST']);
                $host = $parts[0];
            } elseif (!empty($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'];
            } else {
                $host = 'unknown';
            }
        }
        
        return $host;
    }
    
    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getUrlPort($proto)
    {
        $port = '';
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $port = $_SERVER['HTTP_X_FORWARDED_PORT'];
        } elseif (!empty($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        } elseif ($proto === 'https') {
            $port = 443;
        } else {
            $port = 80;
        }
        
        return $port;
    }

    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getUrl()
    {
        $proto = $this->getUrlProto();
        $host = $this->getUrlHost();
        $port = $this->getUrlPort($proto);
        

        $url = $proto . '://' . $host;
        if (($proto == 'https' && $port != 443) || ($proto == 'http' && $port != 80)) {
            $url .= ':' . $port;
        }

        if (isset($_SERVER)) {
            $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
            $url .= $path;
        }

        if ($host == 'unknown') {
            $url = null;
        }

        return $url;
    }

    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getHeaders()
    {
        $headers = array();
        if (isset($_SERVER)) {
            foreach ($_SERVER as $key => $val) {
                if (substr($key, 0, 5) == 'HTTP_') {
                    // convert HTTP_CONTENT_TYPE to Content-Type, HTTP_HOST to Host, etc.
                    $name = strtolower(substr($key, 5));
                    $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', $name)));
                    $headers[$name] = $val;
                }
            }
        }
        if (count($headers) > 0) {
            return $headers;
        } else {
            return null;
        }
    }

    
    protected function getRequestParams()
    {
        return $this->requestParams;
    }

    protected function getRequestBody()
    {
        return $this->requestBody;
    }

    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getUserIp()
    {
        if (!isset($_SERVER) || $this->captureIP === false) {
            return null;
        }
        
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        
        $forwardFor = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;
        if ($forwardFor) {
            // return everything until the first comma
            $parts = explode(',', $forwardFor);
            $ipAddress = $parts[0];
        }
        $realIp = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : null;
        if ($realIp) {
            $ipAddress = $realIp;
        }
        
        if ($this->captureIP === DataBuilder::ANONYMIZE_IP) {
            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $parts = explode('.', $ipAddress);
                $ipAddress = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
            } elseif (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $parts = explode(':', $ipAddress);
                $ipAddress =
                    $parts[0] . ':' .
                    $parts[1] . ':' .
                    $parts[2] . ':' .
                    '0000:0000:0000:0000:0000';
            }
        }
        
        return $ipAddress;
    }

    protected function getRequestExtras()
    {
        return $this->requestExtras;
    }

    /**
     * @return Person
     */
    protected function getPerson()
    {
        $personData = $this->person;
        if (!isset($personData) && is_callable($this->personFunc)) {
            try {
                $personData = call_user_func($this->personFunc);
            } catch (\Exception $exception) {
                Rollbar::scope(array('person_fn' => null))->
                    log(Level::ERROR, $exception);
            }
        }

        if (!isset($personData['id'])) {
            return null;
        }

        $identifier = $personData['id'];

        $email = null;
        if ($this->captureEmail && isset($personData['email'])) {
            $email = $personData['email'];
        }

        $username = null;
        if ($this->captureUsername && isset($personData['username'])) {
            $username = $personData['username'];
        }

        unset($personData['id'], $personData['email'], $personData['username']);
        return new Person($identifier, $username, $email, $personData);
    }

    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getServer()
    {
        $server = new Server();
        $server->setHost($this->getHost())
            ->setRoot($this->getServerRoot())
            ->setBranch($this->getServerBranch())
            ->setCodeVersion($this->getServerCodeVersion());
        $extras = $this->getServerExtras();
        if (!$extras) {
            $extras = array();
        }

        $server->setExtras($extras);
        if (isset($_SERVER) && array_key_exists('argv', $_SERVER)) {
            $server->setArgv($_SERVER['argv']);
        }
        return $server;
    }

    protected function getHost()
    {
        if (isset($this->host)) {
            return $this->host;
        }
        return gethostname();
    }

    protected function getServerRoot()
    {
        return $this->serverRoot;
    }

    protected function getServerBranch()
    {
        return $this->serverBranch;
    }

    protected function getServerCodeVersion()
    {
        return $this->serverCodeVersion;
    }

    protected function getServerExtras()
    {
        return $this->serverExtras;
    }
    
    public function getCustom()
    {
        return $this->custom;
    }
    
    public function getCustomDataMethod()
    {
        return $this->customDataMethod;
    }

    protected function getCustomForPayload($toLog, $context)
    {
        $custom = $this->getCustom();

        // Make this an array if possible:
        if ($custom instanceof \Serializable) {
            $custom = $custom->serialize();
        } elseif (is_null($custom)) {
            $custom = array();
        } elseif (!is_array($custom)) {
            $custom = get_object_vars($custom);
        }
        
        if ($customDataMethod = $this->getCustomDataMethod()) {
            $customDataMethodContext = isset($context['custom_data_method_context']) ?
                $context['custom_data_method_context'] :
                null;
                
            $customDataMethodResult = $customDataMethod($toLog, $customDataMethodContext);
            
            $custom = array_merge($custom, $customDataMethodResult);
        }
        
        unset($context['custom_data_method_context']);

        return $custom;
    }
    
    public function addCustom($key, $data)
    {
        if ($this->custom === null) {
            $this->custom = array();
        }
        
        if (!is_array($this->custom)) {
            throw new \Exception(
                "Custom data configured in Rollbar::init() is not an array."
            );
        }
        
        $this->custom[$key] = $data;
    }
    
    public function removeCustom($key)
    {
        unset($this->custom[$key]);
    }

    protected function getFingerprint($context)
    {
        return isset($context['fingerprint']) ? $context['fingerprint'] : $this->fingerprint;
    }

    protected function getTitle()
    {
        return $this->title;
    }

    protected function getUuid()
    {
        return $this->utilities->uuid4();
    }

    protected function getNotifier()
    {
        return $this->notifier;
    }

    protected function getBaseException()
    {
        return $this->baseException;
    }

    /**
     * Parses an array of code lines from source file with given filename.
     *
     * Attempts to automatically detect the line break character used in the file.
     *
     * @param string $filename
     * @return string[] An array of lines of code from the given source file.
     */
    private function getSourceLines($filename)
    {
        $rawSource = file_get_contents($filename);

        $source = explode(PHP_EOL, $rawSource);

        if (count($source) === 1) {
            if (substr_count($rawSource, "\n") > substr_count($rawSource, "\r")) {
                $source = explode("\n", $rawSource);
            } else {
                $source = explode("\r", $rawSource);
            }
        }

        $source = str_replace(array("\n", "\t", "\r"), '', $source);

        return $source;
    }
    
    /**
     * Wrap a PHP error in an ErrorWrapper class and add backtrace information
     *
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     *
     * @return ErrorWrapper
     */
    public function generateErrorWrapper($errno, $errstr, $errfile, $errline)
    {
        return new ErrorWrapper(
            $errno,
            $errstr,
            $errfile,
            $errline,
            $this->buildErrorTrace($errfile, $errline),
            $this->utilities
        );
    }
    
    /**
     * Fetches the stack trace for fatal and regular errors.
     *
     * @var string $errfile
     * @var string $errline
     *
     * @return Rollbar\ErrorWrapper
     */
    protected function buildErrorTrace($errfile, $errline)
    {
        if ($this->captureErrorStacktraces) {
            $backTrace = $this->fetchErrorTrace();
            
            $backTrace = $this->stripShutdownFrames($backTrace);
            
            // Add the final frame
            array_unshift(
                $backTrace,
                array('file' => $errfile, 'line' => $errline)
            );
        } else {
            $backTrace = array();
        }
        
        return $backTrace;
    }
    
    private function fetchErrorTrace()
    {
        if (function_exists('xdebug_get_function_stack')) {
            return array_reverse(\xdebug_get_function_stack());
        } else {
            return debug_backtrace($this->localVarsDump ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS);
        }
    }
    
    private function stripShutdownFrames($backTrace)
    {
        foreach ($backTrace as $index => $frame) {
            extract($frame);
            
            $fatalHandlerMethod = (isset($method)
                                    && $method === 'Rollbar\\Handlers\\FatalHandler::handle');
                                    
            $fatalHandlerClassAndFunction = (isset($class)
                                                && $class === 'Rollbar\\Handlers\\FatalHandler'
                                                && isset($function)
                                                && $function === 'handle');
            
            $errorHandlerMethod = (isset($method)
                                    && $method === 'Rollbar\\Handlers\\ErrorHandler::handle');
                                    
            $errorHandlerClassAndFunction = (isset($class)
                                                && $class === 'Rollbar\\Handlers\\ErrorHandler'
                                                && isset($function)
                                                && $function === 'handle');
            
            if ($fatalHandlerMethod ||
                 $fatalHandlerClassAndFunction ||
                 $errorHandlerMethod ||
                 $errorHandlerClassAndFunction ) {
                return array_slice($backTrace, $index+1);
            }
        }
        
        return $backTrace;
    }
    
    public function detectGitBranch($allowExec = true)
    {
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
