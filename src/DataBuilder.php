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
    const MAX_PAYLOAD_SIZE = 524288; // 512 * 1024
    
    protected static $truncationStrategies = array(
        "Rollbar\Truncation\RawStrategy",
        "Rollbar\Truncation\FramesStrategy",
        "Rollbar\Truncation\StringsStrategy",
        "Rollbar\Truncation\MinBodyStrategy"
    );
    
    protected static $defaults;

    protected $environment;
    protected $messageLevel;
    protected $exceptionLevel;
    protected $psrLevels;
    protected $scrubFields;
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

    public function __construct($config)
    {
        self::$defaults = Defaults::get();
        $this->setEnvironment($config);

        $this->setDefaultMessageLevel($config);
        $this->setDefaultExceptionLevel($config);
        $this->setDefaultPsrLevels($config);
        $this->setScrubFields($config);
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

        $this->shiftFunction = $this->tryGet($config, 'shift_function');
        if (!isset($this->shiftFunction)) {
            $this->shiftFunction = true;
        }
    }

    protected function getOrCall($name, $level, $toLog, $context)
    {
        if (is_callable($this->$name)) {
            try {
                return $this->$name($level, $toLog, $context);
            } catch (\Exception $e) {
                // TODO Report the configuration error.
                return null;
            }
        }
        return $this->$name;
    }

    protected function tryGet($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    protected function setEnvironment($config)
    {
        $fromConfig = $this->tryGet($config, 'environment');
        Utilities::validateString($fromConfig, "config['environment']", null, false);
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

    protected function setScrubFields($config)
    {
        $fromConfig = $this->tryGet($config, 'scrubFields');
        if (!isset($fromConfig)) {
            $fromConfig = $this->tryGet($config, 'scrub_fields');
        }
        $this->scrubFields = self::$defaults->scrubFields($fromConfig);
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

    protected function setRequestBody($config)
    {
        $this->requestBody = $this->tryGet($config, 'requestBody');
        
        if (!$this->requestBody) {
            $this->requestBody = file_get_contents("php://input");
        }
    }

    protected function setRequestExtras($c)
    {
        $this->requestExtras = $this->tryGet($c, "requestExtras");
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
        $this->serverBranch = self::$defaults->gitBranch($fromConfig);
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

    protected function setHost($config)
    {
        $this->host = $this->tryGet($config, 'host');
    }

    /**
     * @param Level $level
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
        $frames = $this->makeFrames($exception, $includeContext);
        $excInfo = new ExceptionInfo(
            Utilities::coalesce($classOverride, get_class($exception)),
            $exception->getMessage()
        );
        return new Trace($frames, $excInfo);
    }

    public function makeFrames($exception, $includeContext)
    {
        $frames = array();
        foreach ($this->getTrace($exception) as $frameInfo) {
            $filename = Utilities::coalesce($this->tryGet($frameInfo, 'file'), '<internal>');
            $lineno = Utilities::coalesce($this->tryGet($frameInfo, 'line'), 0);
            $method = $frameInfo['function'];
            // TODO 4 (arguments are in $frame)

            $frame = new Frame($filename);
            $frame->setLineno($lineno)
                ->setMethod($method);

            if ($includeContext) {
                $this->addCodeContextToFrame($frame, $filename, $lineno);
            }

            $frames[] = $frame;
        }
        array_reverse($frames);

        if ($this->shiftFunction && count($frames) > 0) {
            for ($i = count($frames) - 1; $i > 0; $i--) {
                $frames[$i]->setMethod($frames[$i - 1]->getMethod());
            }
            $frames[0]->setMethod('<main>');
        }

        return $frames;
    }

    private function addCodeContextToFrame(Frame $frame, $filename, $line)
    {
        if (!file_exists($filename)) {
            return;
        }

        $source = explode(PHP_EOL, file_get_contents($filename));
        if (!is_array($source)) {
            return;
        }

        $source = str_replace(array("\n", "\t", "\r"), '', $source);
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
            $this->sendMessageTrace ? debug_backtrace() : null
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
        $level = strtolower($level);
        return Level::fromName($this->tryGet($this->psrLevels, $level));
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
        return "PHP " . phpversion();
    }

    protected function getFramework()
    {
        return $this->framework;
    }

    protected function getContext()
    {
        return $this->context;
    }

    protected function getRequest()
    {
        $request = new Request();
        $request->setUrl($this->getUrl())
            ->setMethod($this->tryGet($_SERVER, 'REQUEST_METHOD'))
            ->setHeaders($this->getHeaders())
            ->setParams($this->getRequestParams())
            ->setGet($_GET)
            ->setQueryString($this->tryGet($_SERVER, "QUERY_STRING"))
            ->setPost($_POST)
            ->setBody($this->getRequestBody())
            ->setUserIp($this->getUserIp());
        $extras = $this->getRequestExtras();
        if (!$extras) {
            $extras = array();
        }

        foreach ($extras as $key => $val) {
            $request->$key = $val;
        }
        
        if (isset($_SESSION) && is_array($_SESSION) && count($_SESSION) > 0) {
            $request->session = $_SESSION;
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
    
    public function getUrlProto()
    {
        $proto = '';
        
        if (!empty($_SERVER['HTTP_FORWARDED'])) {
            extract($this->parseForwardedString($_SERVER['HTTP_FORWARDED']));
        }
        
        if (empty($proto)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
            } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $proto = 'https';
            } else {
                $proto = 'http';
            }
        }
        
        return $proto;
    }
    
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

    public function getUrl()
    {
        $proto = $this->getUrlProto();
        $host = $this->getUrlHost();
        $port = $this->getUrlPort($proto);
        

        $path = Utilities::coalesce($this->tryGet($_SERVER, 'REQUEST_URI'), '/');
        $url = $proto . '://' . $host;
        if (($proto == 'https' && $port != 443) || ($proto == 'http' && $port != 80)) {
            $url .= ':' . $port;
        }

        $url .= $path;

        if ($host == 'unknown') {
            $url = null;
        }

        return $url;
    }

    protected function getHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $key => $val) {
            if (substr($key, 0, 5) == 'HTTP_') {
                // convert HTTP_CONTENT_TYPE to Content-Type, HTTP_HOST to Host, etc.
                $name = strtolower(substr($key, 5));
                if (strpos($name, '_') != -1) {
                    $name = preg_replace('/ /', '-', ucwords(preg_replace('/_/', ' ', $name)));
                } else {
                    $name = ucfirst($name);
                }
                $headers[$name] = $val;
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

    protected function getUserIp()
    {
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
                    log(Level::fromName("error"), $exception);
            }
        }

        if (!isset($personData['id'])) {
            return null;
        }

        $id = $personData['id'];

        $email = null;
        if (isset($personData['email'])) {
            $email = $personData['email'];
        }

        $username = null;
        if (isset($personData['username'])) {
            $username = $personData['username'];
        }

        unset($personData['id'], $personData['email'], $personData['username']);
        return new Person($id, $username, $email, $personData);
    }

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

        foreach ($extras as $key => $val) {
            $server->$key = $val;
        }
        if (array_key_exists('argv', $_SERVER)) {
            $server->argv = $_SERVER['argv'];
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
        return self::uuid4();
    }

    protected function getNotifier()
    {
        return $this->notifier;
    }

    protected function getBaseException()
    {
        return $this->baseException;
    }

    public function getScrubFields()
    {
        return $this->scrubFields;
    }
    
    /**
     * Scrub a data structure including arrays and query strings.
     *
     * @param mixed $data Data to be scrubbed.
     * @param array $fields Sequence of field names to scrub.
     * @param string $replacement Character used for scrubbing.
     */
    public function scrub(&$data, $replacement = '*')
    {
        $fields = $this->getScrubFields();
        
        if (!$fields || !$data) {
            return $data;
        }
        
        if (is_array($data)) { // scrub arrays
            $data = $this->scrubArray($data, $replacement);
        } elseif (is_string($data)) { // scrub URLs and query strings
            $query = parse_url($data, PHP_URL_QUERY);
            if ($query) {
                $data = str_replace(
                    $query,
                    $this->scrubQueryString($query),
                    $data
                );
            }
        }
        return $data;
    }

    protected function scrubArray(&$arr, $replacement = '*')
    {
        $fields = $this->getScrubFields();
        
        if (!$fields || !$arr) {
            return $arr;
        }
        
        $dataBuilder = $this;

        $scrubber = function (&$val, $key) use ($fields, $replacement, &$scrubber, $dataBuilder) {
            if (in_array($key, $fields, true)) {
                $val = str_repeat($replacement, 8);
            } else {
                $val = $dataBuilder->scrub($val, $replacement);
            }
        };

        array_walk($arr, $scrubber);

        return $arr;
    }

    protected function scrubQueryString($query, $replacement = 'x')
    {
        parse_str($query, $parsed);
        $scrubbed = $this->scrub($parsed, $replacement);
        return http_build_query($scrubbed);
    }

    // from http://www.php.net/manual/en/function.uniqid.php#94959
    protected static function uuid4()
    {
        mt_srand();
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Applies truncation strategies in order to keep the payload size under
     * configured limit.
     *
     * @param array $payload
     * @param string $strategy
     *
     * @return array
     */
    public function truncate(array $payload)
    {
        
        foreach (static::$truncationStrategies as $strategy) {
            if (!$this->needsTruncating($payload)) {
                break;
            }
            
            $strategy = new $strategy($this);
            
            $payload = $strategy->execute($payload);
        }
        
        return $payload;
    }
    
    /**
     * Check if the payload is too big to be sent
     *
     * @param array $payload
     *
     * @return boolean
     */
    public function needsTruncating(array $payload)
    {
        return strlen(json_encode($payload)) > self::MAX_PAYLOAD_SIZE;
    }
}
