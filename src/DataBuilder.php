<?php namespace Rollbar;

use Rollbar\Defaults;
use Rollbar\Payload\Message;
use Rollbar\Payload\Body;
use Rollbar\Payload\Level;
use Rollbar\Payload\Server;
use Rollbar\Payload\Request;
use Rollbar\Payload\Data;
use Rollbar\Payload\Trace;
use Rollbar\Payload\Frame;
use Rollbar\Payload\TraceChain;
use Rollbar\Payload\ExceptionInfo;
use Rollbar\Utilities;
use Rollbar\ErrorWrapper;

class DataBuilder implements DataBuilderInterface
{
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
    protected $person;
    protected $serverRoot;
    protected $serverBranch;
    protected $serverCodeVersion;
    protected $serverExtras;
    protected $custom;
    protected $fingerprint;
    protected $title;
    protected $notifier;
    protected $baseException;

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
        $this->setPerson($config);
        $this->setServerRoot($config);
        $this->setServerBranch($config);
        $this->setServerCodeVersion($config);
        $this->setServerExtras($config);
        $this->setCustom($config);
        $this->setFingerprint($config);
        $this->setTitle($config);
        $this->setNotifier($config);
        $this->setBaseException($config);
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
        $this->scrubFields = self::$defaults->scrubFields($fromConfig);
    }

    protected function setErrorLevels($config)
    {
        $fromConfig = $this->tryGet($config, 'errorLevels');
        $this->errorLevels = self::$defaults->errorLevels($fromConfig);
    }

    protected function setCodeVersion($c)
    {
        $fromConfig = $this->tryGet($c, 'codeVersion');
        $this->codeVersion = self::$defaults->codeVersion($fromConfig);
    }

    protected function setPlatform($c)
    {
        $fromConfig = $this->tryGet($c, 'platform');
        $this->platform = self::$defaults->platform($fromConfig);
    }

    protected function setFramework($c)
    {
        $this->framework = $this->tryGet($c, 'framework');
    }

    protected function setContext($c)
    {
        $this->context = $this->tryGet($c, 'context');
    }

    protected function setRequestParams($c)
    {
        $this->requestParams = $this->tryGet($c, 'requestParams');
    }

    protected function setRequestBody($c)
    {
        $this->requestBody = $this->tryGet($c, 'requestBody');
    }

    protected function setRequestExtras($c)
    {
        $this->requestExtras = $this->tryGet($c, "requestExtras");
    }

    protected function setPerson($c)
    {
        $this->person = $this->tryGet($c, 'person');
    }

    protected function setServerRoot($c)
    {
        $fromConfig = $this->tryGet($c, 'serverRoot');
        $this->serverRoot = self::$defaults->serverRoot($fromConfig);
    }

    protected function setServerBranch($c)
    {
        $fromConfig = $this->tryGet($c, 'serverBranch');
        $this->serverBranch = self::$defaults->gitBranch($fromConfig);
    }

    protected function setServerCodeVersion($c)
    {
        $this->serverCodeVersion = $this->tryGet($c, 'serverCodeVersion');
    }

    protected function setServerExtras($c)
    {
        $this->serverExtras = $this->tryGet($c, 'serverExtras');
    }

    protected function setCustom($c)
    {
        $this->custom = $this->tryGet($c, 'custom');
    }

    protected function setFingerprint($c)
    {
        $this->fingerprint = $this->tryGet($c, 'fingerprint');
        if (!is_null($this->fingerprint) && !is_callable($this->fingerprint)) {
            $msg = "If set, config['fingerprint'] must be a callable that returns a uuid string";
            throw new \InvalidArgumentException($msg);
        }
    }

    protected function setTitle($c)
    {
        $this->title = $this->tryGet($c, 'title');
        if (!is_null($this->title) && !is_callable($this->title)) {
            $msg = "If set, config['title'] must be a callable that returns a string";
            throw new \InvalidArgumentException($msg);
        }
    }

    protected function setNotifier($c)
    {
        $fromConfig = $this->tryGet($c, 'notifier');
        $this->notifier = self::$defaults->notifier($fromConfig);
    }

    protected function setBaseException($c)
    {
        $fromConfig = $this->tryGet($c, 'baseException');
        $this->baseException = self::$defaults->baseException($fromConfig);
    }

    public function makeData($level, $toLog, $context)
    {
        $env = $this->getEnvironment($level, $toLog, $context);
        $body = $this->getBody($level, $toLog, $context);
        $data = new Data($env, $body);
        $data->setLevel($this->getLevel($level, $toLog, $context))
            ->setTimestamp($this->getTimestamp($level, $toLog, $context))
            ->setCodeVersion($this->getCodeVersion($level, $toLog, $context))
            ->setPlatform($this->getPlatform($level, $toLog, $context))
            ->setLanguage($this->getLanguage($level, $toLog, $context))
            ->setFramework($this->getFramework($level, $toLog, $context))
            ->setContext($this->getContext($level, $toLog, $context))
            ->setRequest($this->getRequest($level, $toLog, $context))
            ->setPerson($this->getPerson($level, $toLog, $context))
            ->setServer($this->getServer($level, $toLog, $context))
            ->setCustom($this->getCustom($level, $toLog, $context))
            ->setFingerprint($this->getFingerprint($level, $toLog, $context))
            ->setTitle($this->getTitle($level, $toLog, $context))
            ->setUuid($this->getUuid($level, $toLog, $context))
            ->setNotifier($this->getNotifier($level, $toLog, $context));
        return $data;
    }

    protected function getEnvironment($level, $toLog, $context)
    {
        return $this->getOrCall('environment', $level, $toLog, $context);
    }

    protected function getBody($level, $toLog, $context)
    {
        $baseException = $this->getBaseException($level, $toLog, $context);
        if ($toLog instanceof ErrorWrapper) {
            $content = $this->getErrorTrace($toLog);
        } elseif ($toLog instanceof $baseException) {
            $content = $this->getExceptionTrace($toLog, $baseException);
        } else {
            $scrubFields = $this->getScrubFields($level, $toLog, $context);
            $content = $this->getMessage($toLog, self::scrub($context, $scrubFields));
        }
        return new Body($content);
    }

    protected function getErrorTrace($error)
    {
        return $this->makeTrace($error, $error->getClassName());
    }

    protected function getExceptionTrace($exc)
    {
        $chain = array();
        $chain[] = $this->makeTrace($exc);

        $previous = $exc->getPrevious();

        $baseException = $this->getBaseException();
        while ($previous instanceof $baseException) {
            $chain[] = $this->makeTrace($previous);
            $previous = $exc->getPrevious();
        }

        if (count($chain) > 1) {
            return new TraceChain($chain);
        }
        return new Trace($chain[0]);
    }

    public function makeTrace($exception, $classOverride = null)
    {
        $frames = $this->makeFrames($exception);
        $excInfo = new ExceptionInfo(
            Utilities::coalesce($classOverride, get_class($exception)),
            $exception->getMessage()
        );
        new Trace($frames, $excInfo);
    }

    public function makeFrames($exception)
    {
        $frames = array();
        foreach ($this->getTrace($exception) as $frame) {
            $filename = Utilities::coalesce($this->tryGet($frame, 'file'), '<internal>');
            $lineno = Utilities::coalesce($this->tryGet($frame, 'line'), 0);
            $method = $frame['function'];
            // TODO 4 (arguments are in $frame)
            // TODO 5 Code Context
            $frame = new Frame($filename);
            $frame->setLine($lineno)
                ->setMethod($method);
            $frames[] = $frame;
        }
        array_reverse($frames);
        return $frames;
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
        return new Message((string)$toLog, $context);
    }

    protected function getLevel($level, $toLog, $context)
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

    protected function getTimestamp($level, $toLog, $context)
    {
        return time();
    }

    protected function getCodeVersion($level, $toLog, $context)
    {
        return $this->getOrCall('codeVersion', $level, $toLog, $context);
    }

    protected function getPlatform($level, $toLog, $context)
    {
        return $this->getOrCall('platform', $level, $toLog, $context);
    }

    protected function getLanguage($level, $toLog, $context)
    {
        return "PHP " . phpversion();
    }

    protected function getFramework($level, $toLog, $context)
    {
        return $this->getOrCall('framework', $level, $toLog, $context);
    }

    protected function getContext($level, $toLog, $context)
    {
        return $this->getOrCall('context', $level, $toLog, $context);
    }

    protected function getRequest($level, $toLog, $context)
    {
        $scrubFields = $this->getScrubFields($level, $toLog, $context);
        $request = new Request();
        $request->setUrl($this->getUrl($scrubFields))
            ->setMethod($this->tryGet($_SERVER, 'REQUEST_METHOD'))
            ->setHeaders($this->getScrubbedHeaders($scrubFields))
            ->setParams($this->getRequestParams($level, $toLog, $context))
            ->setGet(self::scrub($_GET, $scrubFields))
            ->setQueryString(self::scrubUrl($this->tryGet($_SERVER, "QUERY_STRING"), $scrubFields))
            ->setPost(self::scrub($_POST, $scrubFields))
            ->setBody($this->getRequestBody($level, $toLog, $context))
            ->setuserIp($this->getUserIp());
        $extras = $this->getRequestExtras($level, $toLog, $context);
        if (!$extras) {
            $extras = array();
        }
        foreach ($extras as $key => $val) {
            if (in_array($scrubFields, $key)) {
                $request->$key = str_repeat("*", 8);
            } else {
                $request->$key = $val;
            }
        }
        if (is_array($_SESSION)) {
            $request->session = self::scrub($_SESSION, $scrubFields);
        }
        return $request;
    }

    protected function getUrl($scrubFields)
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
        } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $proto = 'https';
        } else {
            $proto = 'http';
        }

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

        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $port = $_SERVER['HTTP_X_FORWARDED_PORT'];
        } elseif (!empty($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        } elseif ($proto === 'https') {
            $port = 443;
        } else {
            $port = 80;
        }

        $path = Utilities::coalesce($this->tryGet($_SERVER, 'REQUEST_URI'), '/');
        $url = $proto . '://' . $host;
        if (($proto == 'https' && $port != 443) || ($proto == 'http' && $port != 80)) {
            $url .= ':' . $port;
        }

        $url .= $path;

        return self::scrubUrl($url, $scrubFields);
    }

    protected function getScrubbedHeaders($scrubFields)
    {
        $headers = $this->getHeaders();
        return self::scrub($headers, $scrubFields);
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

    protected function getRequestParams($level, $toLog, $context)
    {
        return $this->getOrCall('requestParams', $level, $toLog, $context);
    }

    protected function getRequestBody($level, $toLog, $context)
    {
        return $this->getOrCall('requestBody', $level, $toLog, $context);
    }

    protected function getUserIp()
    {
        $forwardfor = $this->tryGet($_SERVER, 'HTTP_X_FORWARDED_FOR');
        if ($forwardfor) {
            // return everything until the first comma
            $parts = explode(',', $forwardfor);
            return $parts[0];
        }
        $realip = $this->tryGet($_SERVER, 'HTTP_X_REAL_IP');
        if ($realip) {
            return $realip;
        }
        return $this->tryGet($_SERVER, 'REMOTE_ADDR');
    }

    protected function getRequestExtras($level, $toLog, $context)
    {
        return $this->getOrCall('requestExtras', $level, $toLog, $context);
    }

    protected function getPerson($level, $toLog, $context)
    {
        return $this->getOrCall('person', $level, $toLog, $context);
    }

    protected function getServer($level, $toLog, $context)
    {
        $server = new Server();
        fwrite(STDERR, print_r($server, true));
        fwrite(STDERR, implode(PHP_EOL, get_included_files()));
        $server->setHost(gethostname())
            ->setRoot($this->getServerRoot($level, $toLog, $context))
            ->setBranch($this->getServerBranch($level, $toLog, $context))
            ->setCodeVersion($this->getServerCodeVersion($level, $toLog, $context));
        $scrubFields = $this->getScrubFields($level, $toLog, $context);
        $extras = $this->getServerExtras($level, $toLog, $context);
        if (!$extras) {
            $extras = array();
        }
        foreach ($extras as $key => $val) {
            if (in_array($scrubFields, $key)) {
                $server->$key = str_repeat("*", 8);
            } {
                $server->$key = $val;
            }
        }
        if (array_key_exists('argv', $_SERVER)) {
            $server->argv = $_SERVER['argv'];
        }
        return $server;
    }

    protected function getServerRoot($level, $toLog, $context)
    {
        return $this->getOrCall('serverRoot', $level, $toLog, $context);
    }

    protected function getServerBranch($level, $toLog, $context)
    {
        return $this->getOrCall('serverBranch', $level, $toLog, $context);
    }

    protected function getServerCodeVersion($level, $toLog, $context)
    {
        return $this->getOrCall('serverCodeVersion', $level, $toLog, $context);
    }

    protected function getServerExtras($level, $toLog, $context)
    {
        return $this->getOrCall('serverExtras', $level, $toLog, $context);
    }

    protected function getCustom($level, $toLog, $context)
    {
        $custom = $this->getOrCall('custom', $level, $toLog, $context);

        // Make this an array if possible:
        if ($custom instanceof \JsonSerializable) {
            $custom = $custom->jsonSerialize();
        } elseif (is_null($custom)) {
            return null;
        } elseif (!is_array($custom)) {
            $custom = get_object_vars($custom);
        }
        // If toLog is a message:
        $baseException = $this->getBaseException($level, $toLog, $context);
        if (!$toLog instanceof $baseException) {
            return array_replace_recursive(array(), $custom);
        }

        $scrubFields = $this->getScrubFields($level, $toLog, $context);
        $custom = self::scrub($custom, $scrubFields);
        return array_replace_recursive(array(), $context, $custom);
    }

    protected function getFingerprint($level, $toLog, $context)
    {
        return $this->getOrCall('fingerprint', $level, $toLog, $context);
    }

    protected function getTitle($level, $toLog, $context)
    {
        return $this->getOrCall('title', $level, $toLog, $context);
    }

    protected function getUuid($level, $toLog, $context)
    {
        return self::uuid4();
    }

    protected function getNotifier($level, $toLog, $context)
    {
        return $this->getOrCall('notifier', $level, $toLog, $context);
    }

    protected function getBaseException($level, $toLog, $context)
    {
        return $this->getOrCall('baseException', $level, $toLog, $context);
    }

    protected function getScrubFields($level, $toLog, $context)
    {
        return $this->getOrCall('scrubFields', $level, $toLog, $context);
    }

    protected function scrub($arr, $fields, $replacement = '*')
    {
        if (!$fields || !$arr) {
            return $fields;
        }
        $fields = $this->scrubFields;
        $scrubber = function ($key, &$val) use ($fields) {

            if (in_array($key, $arr)) {
                $val = str_repeat($replacement, 8);
            }
        };
        array_walk_recursive($arr, $scrubber);
        return $arr;
    }

    protected function scrubUrl($url, $fields)
    {
        $urlQuery = parse_url($url, PHP_URL_QUERY);
        if (!$urlQuery) {
            return $url;
        }

        parse_str($urlQuery, $parsedOutput);
        $scrubbedOutput = $this->scrub($parsedOutput, $fields, 'x');

        return str_replace($urlQuery, http_build_query($scrubbedOutput), $url);
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
}
