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
    protected $fingerprint;
    protected $title;
    protected $notifier;
    protected $baseException;
    protected $includeCodeContext;
    protected $includeExcCodeContext;
    protected $shiftFunction;
    protected $sendMessageTrace;
    protected $rawRequestBody;
    protected $localVarsDump;
    protected $captureErrorStacktraces;
    
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

        $this->shiftFunction = $this->tryGet($config, 'shift_function');
        if (!isset($this->shiftFunction)) {
            $this->shiftFunction = true;
        }
    }

    protected function tryGet($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    protected function setEnvironment($config)
    {
        $fromConfig = $this->tryGet($config, 'environment');
        $this->utilities->validateString($fromConfig, "config['environment']", null, false);
        $this->environment = $fromConfig;
    }

    protected function setDefaultMessageLevel($config)
    {
        $fromConfig = $this->tryGet($config, 'messageLevel');
        $this->messageLevel = self::$defaults->messageLevel($fromConfig);
    }

    protected function setDefaultExceptionLevel($config)
    {
        $fromConfig = $this->tryGet($config, 'exceptionLevel');
        $this->exceptionLevel = self::$defaults->exceptionLevel($fromConfig);
    }

    protected function setDefaultPsrLevels($config)
    {
        $fromConfig = $this->tryGet($config, 'psrLevels');
        $this->psrLevels = self::$defaults->psrLevels($fromConfig);
    }

    protected function setErrorLevels($config)
    {
        $fromConfig = $this->tryGet($config, 'errorLevels');
        $this->errorLevels = self::$defaults->errorLevels($fromConfig);
    }

    protected function setSendMessageTrace($config)
    {
        $fromConfig = $this->tryGet($config, 'send_message_trace');
        $this->sendMessageTrace = self::$defaults->sendMessageTrace($fromConfig);
    }
    
    protected function setRawRequestBody($config)
    {
        $fromConfig = $this->tryGet($config, 'include_raw_request_body');
        $this->rawRequestBody = self::$defaults->rawRequestBody($fromConfig);
    }

    protected function setLocalVarsDump($config)
    {
        $fromConfig = $this->tryGet($config, 'local_vars_dump');
        $this->localVarsDump = self::$defaults->localVarsDump($fromConfig);
    }
    
    protected function setCaptureErrorStacktraces($config)
    {
        $fromConfig = $this->tryGet($config, 'capture_error_stacktraces');
        $this->captureErrorStacktraces = self::$defaults->captureErrorStacktraces($fromConfig);
    }

    protected function setCodeVersion($config)
    {
        $fromConfig = $this->tryGet($config, 'codeVersion');
        if (!isset($fromConfig)) {
            $fromConfig = $this->tryGet($config, 'code_version');
        }
        $this->codeVersion = self::$defaults->codeVersion($fromConfig);
    }

    protected function setPlatform($config)
    {
        $fromConfig = $this->tryGet($config, 'platform');
        $this->platform = self::$defaults->platform($fromConfig);
    }

    protected function setFramework($config)
    {
        $this->framework = $this->tryGet($config, 'framework');
    }

    protected function setContext($config)
    {
        $this->context = $this->tryGet($config, 'context');
    }

    protected function setRequestParams($config)
    {
        $this->requestParams = $this->tryGet($config, 'requestParams');
    }

    /*
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function setRequestBody($config)
    {
        
        $this->requestBody = $this->tryGet($config, 'requestBody');
        
        if (!$this->requestBody && $this->rawRequestBody) {
            $this->requestBody = file_get_contents("php://input");
            if (version_compare(PHP_VERSION, '5.6.0') < 0) {
                $_SERVER['php://input'] = $this->requestBody;
            }
        }
    }

    protected function setRequestExtras($config)
    {
        $this->requestExtras = $this->tryGet($config, "requestExtras");
    }

    protected function setPerson($config)
    {
        $this->person = $this->tryGet($config, 'person');
    }

    protected function setPersonFunc($config)
    {
        $this->personFunc = $this->tryGet($config, 'person_fn');
    }

    protected function setServerRoot($config)
    {
        $fromConfig = $this->tryGet($config, 'serverRoot');
        if (!isset($fromConfig)) {
            $fromConfig = $this->tryGet($config, 'root');
        }
        $this->serverRoot = self::$defaults->serverRoot($fromConfig);
    }

    protected function setServerBranch($config)
    {
        $fromConfig = $this->tryGet($config, 'serverBranch');
        if (!isset($fromConfig)) {
            $fromConfig = $this->tryGet($config, 'branch');
        }
        $allowExec = $this->tryGet($config, 'allow_exec');
        if (!isset($allowExec)) {
            $allowExec = true;
        }
        $this->serverBranch = self::$defaults->gitBranch($fromConfig, $allowExec);
    }

    protected function setServerCodeVersion($config)
    {
        $this->serverCodeVersion = $this->tryGet($config, 'serverCodeVersion');
    }

    protected function setServerExtras($config)
    {
        $this->serverExtras = $this->tryGet($config, 'serverExtras');
    }

    protected function setCustom($config)
    {
        $this->custom = $this->tryGet($config, 'custom');
    }

    protected function setFingerprint($config)
    {
        $this->fingerprint = $this->tryGet($config, 'fingerprint');
        if (!is_null($this->fingerprint) && !is_callable($this->fingerprint)) {
            $msg = "If set, config['fingerprint'] must be a callable that returns a uuid string";
            throw new \InvalidArgumentException($msg);
        }
    }

    protected function setTitle($config)
    {
        $this->title = $this->tryGet($config, 'title');
        if (!is_null($this->title) && !is_callable($this->title)) {
            $msg = "If set, config['title'] must be a callable that returns a string";
            throw new \InvalidArgumentException($msg);
        }
    }

    protected function setNotifier($config)
    {
        $fromConfig = $this->tryGet($config, 'notifier');
        $this->notifier = self::$defaults->notifier($fromConfig);
    }

    protected function setBaseException($config)
    {
        $fromConfig = $this->tryGet($config, 'baseException');
        $this->baseException = self::$defaults->baseException($fromConfig);
    }

    protected function setIncludeCodeContext($config)
    {
        $fromConfig = $this->tryGet($config, 'include_error_code_context');
        $this->includeCodeContext = self::$defaults->includeCodeContext($fromConfig);
    }

    protected function setIncludeExcCodeContext($config)
    {
        $fromConfig = $this->tryGet($config, 'include_exception_code_context');
        $this->includeExcCodeContext = self::$defaults->includeExcCodeContext($fromConfig);
    }
    
    protected function setLevelFactory($config)
    {
        $this->levelFactory = $this->tryGet($config, 'levelFactory');
        if (!$this->levelFactory) {
            throw new \InvalidArgumentException(
                'Missing dependency: LevelFactory not provided to the DataBuilder.'
            );
        }
    }
    
    protected function setUtilities($config)
    {
        $this->utilities = $this->tryGet($config, 'utilities');
        if (!$this->utilities) {
            throw new \InvalidArgumentException(
                'Missing dependency: Utilities not provided to the DataBuilder.'
            );
        }
    }

    protected function setHost($config)
    {
        $this->host = $this->tryGet($config, 'host');
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
            ->setCustom($this->getCustom($toLog, $context))
            ->setFingerprint($this->getFingerprint())
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
            $content = $this->getMessage($toLog, $context);
        }
        return new Body($content);
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
            $this->utilities->coalesce($classOverride, get_class($exception)),
            $exception->getMessage()
        );
        return new Trace($frames, $excInfo);
    }

    public function makeFrames($exception, $includeContext)
    {
        $frames = array();
        foreach ($this->getTrace($exception) as $frameInfo) {
            $filename = $this->utilities->coalesce($this->tryGet($frameInfo, 'file'), '<internal>');
            $lineno = $this->utilities->coalesce($this->tryGet($frameInfo, 'line'), 0);
            $method = $frameInfo['function'];
            $args = $this->utilities->coalesce($this->tryGet($frameInfo, 'args'), null);

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

        if ($this->shiftFunction && count($frames) > 0) {
            for ($i = count($frames) - 1; $i > 0; $i--) {
                $frames[$i]->setMethod($frames[$i - 1]->getMethod());
            }
            $frames[0]->setMethod('<main>');
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
        $line = $line - 1;
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
            return $exc->getTrace();
        }
    }

    protected function getMessage($toLog, $context)
    {
        return new Message(
            (string)$toLog,
            $context,
            $this->sendMessageTrace ?
                debug_backtrace($this->localVarsDump ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS) :
                null
        );
    }

    protected function getLevel($level, $toLog)
    {
        if (is_null($level)) {
            if ($toLog instanceof ErrorWrapper) {
                $level = $this->tryGet($this->errorLevels, $toLog->errorLevel);
            } elseif ($toLog instanceof \Exception) {
                $level = $this->exceptionLevel;
            } else {
                $level = $this->messageLevel;
            }
        }
        $level = $this->tryGet($this->psrLevels, strtolower($level));
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
            $request->setMethod($this->tryGet($_SERVER, 'REQUEST_METHOD'))
                ->setQueryString($this->tryGet($_SERVER, "QUERY_STRING"));
        }
      
        if (isset($_GET)) {
            $request->setGet($_GET);
        }
        if (isset($_POST)) {
            $request->setPost($_POST);
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
            $path = $this->utilities->coalesce($this->tryGet($_SERVER, 'REQUEST_URI'), '/');
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
        if (!isset($_SERVER)) {
            return null;
        }
        $forwardFor = $this->tryGet($_SERVER, 'HTTP_X_FORWARDED_FOR');
        if ($forwardFor) {
            // return everything until the first comma
            $parts = explode(',', $forwardFor);
            return $parts[0];
        }
        $realIp = $this->tryGet($_SERVER, 'HTTP_X_REAL_IP');
        if ($realIp) {
            return $realIp;
        }
        return $this->tryGet($_SERVER, 'REMOTE_ADDR');
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
        if (isset($personData['email'])) {
            $email = $personData['email'];
        }

        $username = null;
        if (isset($personData['username'])) {
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

    protected function getCustom($toLog, $context)
    {
        $custom = $this->custom;

        // Make this an array if possible:
        if ($custom instanceof \JsonSerializable) {
            $custom = $custom->jsonSerialize();
        } elseif (is_null($custom)) {
            return null;
        } elseif (!is_array($custom)) {
            $custom = get_object_vars($custom);
        }

        $baseException = $this->getBaseException();
        if (!$toLog instanceof $baseException) {
            return array_replace_recursive(array(), $custom);
        }

        return array_replace_recursive(array(), $context, $custom);
    }

    protected function getFingerprint()
    {
        return $this->fingerprint;
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
        if ($this->captureErrorStacktraces) {
            $backTrace = array_slice(
                debug_backtrace($this->localVarsDump ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS),
                2
            );
        } else {
            $backTrace = array();
        }
        return new ErrorWrapper(
            $errno,
            $errstr,
            $errfile,
            $errline,
            $backTrace,
            $this->utilities
        );
    }
}
