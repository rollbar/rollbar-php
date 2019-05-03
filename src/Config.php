<?php namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use \Rollbar\Payload\EncodedPayload;

if (!defined('ROLLBAR_INCLUDED_ERRNO_BITMASK')) {
    define(
        'ROLLBAR_INCLUDED_ERRNO_BITMASK',
        E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR
    );
}

class Config
{
    const VERBOSE_NONE = 'none';
    const VERBOSE_NONE_INT = 1000;

    private static $options = array(
        'access_token',
        'agent_log_location',
        'allow_exec',
        'endpoint',
        'base_api_url',
        'autodetect_branch',
        'branch',
        'capture_error_stacktraces',
        'check_ignore',
        'code_version',
        'custom',
        'custom_data_method',
        'enabled',
        'environment',
        'error_sample_rates',
        'exception_sample_rates',
        'fluent_host',
        'fluent_port',
        'fluent_tag',
        'handler',
        'host',
        'include_error_code_context',
        'include_exception_code_context',
        'included_errno',
        'log_payload',
        'log_payload_logger',
        'person',
        'person_fn',
        'capture_ip',
        'capture_username',
        'capture_email',
        'root',
        'scrub_fields',
        'scrub_whitelist',
        'timeout',
        'transmit',
        'custom_truncation',
        'report_suppressed',
        'use_error_reporting',
        'proxy',
        'send_message_trace',
        'include_raw_request_body',
        'local_vars_dump',
        'max_nesting_depth',
        'max_items',
        'minimum_level',
        'verbose',
        'verbose_logger',
        'raise_on_error'
    );
    
    private $accessToken;
    /**
     * @var boolean $enabled If this is false then do absolutely nothing,
     * try to be as close to the scenario where Rollbar did not exist at
     * all in the code.
     * Default: true
     */
    private $enabled = true;

    /**
     * @var boolean $transmit If this is false then we do everything except
     * make the post request at the end of the pipeline.
     * Default: true
     */
    private $transmit;

    /**
     * @var boolean $logPayload If this is true then we output the payload to
     * standard out or a configured logger right before transmitting.
     * Default: false
     */
    private $logPayload;

    /**
     * @var \Psr\Log\Logger $logPayloadLogger Logger responsible for logging request
     * payload and response dumps on. The messages logged can be controlled with
     * `log_payload` config options.
     * Default: \Monolog\Logger with \Monolog\Handler\ErrorLogHandler
     */
    private $logPayloadLogger;

    /**
     * @var string $verbose If this is set to any of the \Psr\Log\LogLevel options
     * then we output messages related to the processing of items that might be
     * useful to someone trying to understand what Rollbar is doing. The logged
     * messages are dependent on the level of verbosity. The supported options are
     * all the log levels of \Psr\Log\LogLevel
     * (https://github.com/php-fig/log/blob/master/Psr/Log/LogLevel.php) plus
     * an additional Rollbar\Config::VERBOSE_NONE option which makes the SDK quiet
     * (excluding `log_payload` option configured separetely).
     * Essentially this option controls the level of verbosity of the default
     * `verbose_logger`. If you override the default `verbose_logger`, you need
     * to implement obeying the `verbose` config option yourself.
     * Default: Rollbar\Config::VERBOSE_NONE
     */
    private $verbose;

    /**
     * @var \Psr\Log\Logger $versbosity_logger The logger object used to log
     * the internal messages of the SDK. The verbosity level of the default
     * $verbosityLogger can be controlled with `verbose` config option.
     * Default: \Rollbar\VerboseLogger
     */
    private $verboseLogger;

    /**
     * @var DataBuilder
     */
    private $dataBuilder;
    private $configArray;
    
    /**
     * @var LevelFactory
     */
    private $levelFactory;
    
    /**
     * @var Utilities
     */
    private $utilities;
    
    /**
     * @var TransformerInterface
     */
    private $transformer;
    /**
     * @var FilterInterface
     */
    private $filter;
    
    /**
     * @var int
     */
    private $minimumLevel;
    
    /**
     * @var ResponseHandlerInterface
     */
    private $responseHandler;
    /**
     * @var \Rollbar\Senders\SenderInterface
     */
    private $sender;
    private $reportSuppressed;
    /**
     * @var Scrubber
     */
    private $scrubber;

    private $batched = false;
    private $batchSize = 50;

    private $maxNestingDepth = 10;

    private $custom = array();
    
    /**
     * @var callable with parameters $toLog, $contextDataMethodContext. The return
     * value of the callable will be appended to the custom field of the item.
     */
    private $customDataMethod;
    
    /**
     * @var callable
     */
    private $checkIgnore;
    private $errorSampleRates;
    private $exceptionSampleRates;
    private $mtRandmax;

    private $includedErrno;
    private $useErrorReporting = false;
    
    /**
     * @var boolean Should debug_backtrace() data be sent with string messages
     * sent through RollbarLogger::log().
     */
    private $sendMessageTrace = false;
    
    /**
     * @var string (fully qualified class name) The name of the your custom
     * truncation strategy class. The class should inherit from
     * Rollbar\Truncation\AbstractStrategy.
     */
    private $customTruncation;
    
    /**
     * @var boolean Should the SDK raise an exception after logging an error.
     * This is useful in test and development enviroments.
     * https://github.com/rollbar/rollbar-php/issues/448
     */
    private $raiseOnError = false;
    
    /**
     * @var int The maximum number of items reported to Rollbar within one
     * request.
     */
    private $maxItems;

    public function __construct(array $configArray)
    {
        $this->includedErrno = \Rollbar\Defaults::get()->includedErrno();
        
        $this->levelFactory = new LevelFactory();
        $this->utilities = new Utilities();
        
        $this->updateConfig($configArray);

        $this->errorSampleRates = \Rollbar\Defaults::get()->errorSampleRates();
        if (isset($configArray['error_sample_rates'])) {
            $this->errorSampleRates = $configArray['error_sample_rates'];
        }
        
        $this->exceptionSampleRates = \Rollbar\Defaults::get()->exceptionSampleRates();
        if (isset($configArray['exception_sample_rates'])) {
            $this->exceptionSampleRates = $configArray['exception_sample_rates'];
        }

        $levels = array(E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING,
            E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR);
        // PHP 5.3.0
        if (defined('E_DEPRECATED')) {
            $levels = array_merge($levels, array(E_DEPRECATED, E_USER_DEPRECATED));
        }
        $curr = 1;
        for ($i = 0, $num = count($levels); $i < $num; $i++) {
            $level = $levels[$i];
            if (!isset($this->errorSampleRates[$level])) {
                $this->errorSampleRates[$level] = $curr;
            }
        }
        $this->mtRandmax = mt_getrandmax();
    }
    
    public static function listOptions()
    {
        return self::$options;
    }

    public function configure($config)
    {
        $this->updateConfig($this->extend($config));
    }

    public function extend($config)
    {
        return array_replace_recursive(array(), $this->configArray, $config);
    }

    public function getConfigArray()
    {
        return $this->configArray;
    }

    protected function updateConfig($config)
    {
        $this->configArray = $config;

        $this->setEnabled($config);
        $this->setTransmit($config);
        $this->setLogPayload($config);
        $this->setLogPayloadLogger($config);
        $this->setVerbose($config);
        $this->setVerboseLogger($config);
        $this->setAccessToken($config);
        $this->setDataBuilder($config);
        $this->setTransformer($config);
        $this->setMinimumLevel($config);
        $this->setReportSuppressed($config);
        $this->setFilters($config);
        $this->setSender($config);
        $this->setScrubber($config);
        $this->setBatched($config);
        $this->setBatchSize($config);
        $this->setMaxNestingDepth($config);
        $this->setCustom($config);
        $this->setResponseHandler($config);
        $this->setCheckIgnoreFunction($config);
        $this->setSendMessageTrace($config);
        $this->setRaiseOnError($config);

        if (isset($config['included_errno'])) {
            $this->includedErrno = $config['included_errno'];
        }

        $this->useErrorReporting = \Rollbar\Defaults::get()->useErrorReporting();
        if (isset($config['use_error_reporting'])) {
            $this->useErrorReporting = $config['use_error_reporting'];
        }
        
        $this->maxItems = \Rollbar\Defaults::get()->maxItems();
        if (isset($config['max_items'])) {
            $this->maxItems = $config['max_items'];
        }
        
        if (isset($config['custom_truncation'])) {
            $this->customTruncation = $config['custom_truncation'];
        }
        
        $this->customDataMethod = \Rollbar\Defaults::get()->customDataMethod();
        if (isset($config['custom_data_method'])) {
            $this->customDataMethod = $config['custom_data_method'];
        }
    }

    private function setAccessToken($config)
    {
        if (isset($_ENV['ROLLBAR_ACCESS_TOKEN']) && !isset($config['access_token'])) {
            $config['access_token'] = $_ENV['ROLLBAR_ACCESS_TOKEN'];
        }
        $this->utilities->validateString($config['access_token'], "config['access_token']", 32, false);
        $this->accessToken = $config['access_token'];
    }

    private function setEnabled($config)
    {
        if (array_key_exists('enabled', $config) && $config['enabled'] === false) {
            $this->disable();
        } else {
            if (\Rollbar\Defaults::get()->enabled() === false) {
                $this->disable();
            } else {
                $this->enable();
            }
        }
    }

    private function setTransmit($config)
    {
        $this->transmit = isset($config['transmit']) ?
            $config['transmit'] :
            \Rollbar\Defaults::get()->transmit();
    }

    private function setLogPayload($config)
    {
        $this->logPayload = isset($config['log_payload']) ?
            $config['log_payload'] :
            \Rollbar\Defaults::get()->logPayload();
    }

    private function setLogPayloadLogger($config)
    {
        $this->logPayloadLogger = isset($config['log_payload_logger']) ?
            $config['log_payload_logger'] :
            new \Monolog\Logger('rollbar.payload', array(new \Monolog\Handler\ErrorLogHandler()));
        
        if (!($this->logPayloadLogger instanceof \Psr\Log\LoggerInterface)) {
            throw new \Exception('Log Payload Logger must implement \Psr\Log\LoggerInterface');
        }
    }

    private function setVerbose($config)
    {
        $this->verbose = isset($config['verbose']) ?
            $config['verbose'] :
            \Rollbar\Defaults::get()->verbose();
    }

    private function setVerboseLogger($config)
    {
        if (isset($config['verbose_logger'])) {
            $this->verboseLogger = $config['verbose_logger'];
        } else {
            $handler = new \Monolog\Handler\ErrorLogHandler();
            $handler->setLevel($this->verboseInteger());
            $this->verboseLogger = new \Monolog\Logger('rollbar.verbose', array($handler));
        }
        
        if (!($this->verboseLogger instanceof \Psr\Log\LoggerInterface)) {
            throw new \Exception('Verbose logger must implement \Psr\Log\LoggerInterface');
        }
    }
    
    public function enable()
    {
        $this->enabled = true;
    }
    
    public function disable()
    {
        $this->enabled = false;
    }

    private function setDataBuilder($config)
    {
        if (!isset($config['levelFactory'])) {
            $config['levelFactory'] = $this->levelFactory;
        }
        
        if (!isset($config['utilities'])) {
            $config['utilities'] = $this->utilities;
        }
        
        $exp = "Rollbar\DataBuilderInterface";
        $def = "Rollbar\DataBuilder";
        $this->setupWithOptions($config, "dataBuilder", $exp, $def, true);
    }

    private function setTransformer($config)
    {
        $expected = "Rollbar\TransformerInterface";
        $this->setupWithOptions($config, "transformer", $expected);
    }

    private function setMinimumLevel($config)
    {
        $this->minimumLevel = \Rollbar\Defaults::get()->minimumLevel();
        
        $override = array_key_exists('minimum_level', $config) ? $config['minimum_level'] : null;
        $override = array_key_exists('minimumLevel', $config) ? $config['minimumLevel'] : $override;
        
        if ($override instanceof Level) {
            $this->minimumLevel = $override->toInt();
        } elseif (is_string($override)) {
            $level = $this->levelFactory->fromName($override);
            if ($level !== null) {
                $this->minimumLevel = $level->toInt();
            }
        } elseif (is_int($override)) {
            $this->minimumLevel = $override;
        }
    }

    private function setReportSuppressed($config)
    {
        $this->reportSuppressed = isset($config['reportSuppressed']) && $config['reportSuppressed'];
        if (!isset($this->reportSuppressed)) {
            $this->reportSuppressed = isset($config['report_suppressed']) && $config['report_suppressed'];
        }
        
        if (!isset($this->reportSuppressed)) {
            $this->reportSuppressed = \Rollbar\Defaults::get()->reportSuppressed();
        }
    }

    private function setFilters($config)
    {
        $this->setupWithOptions($config, "filter", "Rollbar\FilterInterface");
    }

    private function setSender($config)
    {
        $expected = "Rollbar\Senders\SenderInterface";
        
        $default = "Rollbar\Senders\CurlSender";

        $this->setTransportOptions($config);
        $default = $this->setAgentSenderOptions($config, $default);
        $default = $this->setFluentSenderOptions($config, $default);

        $this->setupWithOptions($config, "sender", $expected, $default);
    }

    private function setScrubber($config)
    {
        $exp = "Rollbar\ScrubberInterface";
        $def = "Rollbar\Scrubber";
        $this->setupWithOptions($config, "scrubber", $exp, $def, true);
    }

    private function setBatched($config)
    {
        if (array_key_exists('batched', $config)) {
            $this->batched = $config['batched'];
        }
    }
    
    private function setRaiseOnError($config)
    {
        if (array_key_exists('raise_on_error', $config)) {
            $this->raiseOnError = $config['raise_on_error'];
        } else {
            $this->raiseOnError = \Rollbar\Defaults::get()->raiseOnError();
        }
    }

    private function setBatchSize($config)
    {
        if (array_key_exists('batch_size', $config)) {
            $this->batchSize = $config['batch_size'];
        }
    }

    private function setMaxNestingDepth($config)
    {
        if (array_key_exists('max_nesting_depth', $config)) {
            $this->maxNestingDepth = $config['max_nesting_depth'];
        }
    }

    public function setCustom($config)
    {
        $this->dataBuilder->setCustom($config);
    }
    
    public function addCustom($key, $data)
    {
        $this->dataBuilder->addCustom($key, $data);
    }
    
    public function removeCustom($key)
    {
        $this->dataBuilder->removeCustom($key);
    }

    public function transmitting()
    {
        return $this->transmit;
    }

    public function loggingPayload()
    {
        return $this->logPayload;
    }

    public function verbose()
    {
        return $this->verbose;
    }

    public function verboseInteger()
    {
        if ($this->verbose == self::VERBOSE_NONE) {
            return self::VERBOSE_NONE_INT;
        }
        return \Monolog\Logger::toMonologLevel($this->verbose);
    }
    
    public function getCustom()
    {
        return $this->dataBuilder->getCustom();
    }
    
    public function getAllowedCircularReferenceTypes()
    {
        return $this->allowedCircularReferenceTypes;
    }
    
    public function setCustomTruncation($type)
    {
        $this->customTruncation = $type;
    }
    
    public function getCustomTruncation()
    {
        return $this->customTruncation;
    }

    private function setTransportOptions(&$config)
    {
        if (array_key_exists('base_api_url', $config)) {
            $config['senderOptions']['endpoint'] = $config['base_api_url'] . 'item/';
        }

        if (array_key_exists('endpoint', $config)) {
            $config['senderOptions']['endpoint'] = $config['endpoint'] . 'item/';
        }

        if (array_key_exists('timeout', $config)) {
            $config['senderOptions']['timeout'] = $config['timeout'];
        }

        if (array_key_exists('proxy', $config)) {
            $config['senderOptions']['proxy'] = $config['proxy'];
        }

        if (array_key_exists('ca_cert_path', $config)) {
            $config['senderOptions']['ca_cert_path'] = $config['ca_cert_path'];
        }
    }

    private function setAgentSenderOptions(&$config, $default)
    {
        if (!array_key_exists('handler', $config) || $config['handler'] != 'agent') {
            return $default;
        }
        $default = "Rollbar\Senders\AgentSender";
        if (array_key_exists('agent_log_location', $config)) {
            $config['senderOptions'] = array(
                'agentLogLocation' => $config['agent_log_location']
            );
        }
        return $default;
    }

    private function setFluentSenderOptions(&$config, $default)
    {
        if (!isset($config['handler']) || $config['handler'] != 'fluent') {
            return $default;
        }
        $default = "Rollbar\Senders\FluentSender";

        if (isset($config['fluent_host'])) {
            $config['senderOptions']['fluentHost'] = $config['fluent_host'];
        }

        if (isset($config['fluent_port'])) {
            $config['senderOptions']['fluentPort'] = $config['fluent_port'];
        }

        if (isset($config['fluent_tag'])) {
            $config['senderOptions']['fluentTag'] = $config['fluent_tag'];
        }

        return $default;
    }

    private function setResponseHandler($config)
    {
        $this->setupWithOptions($config, "responseHandler", "Rollbar\ResponseHandlerInterface");
    }

    private function setCheckIgnoreFunction($config)
    {
        // Remain backwards compatible
        if (isset($config['checkIgnore'])) {
            $this->checkIgnore = $config['checkIgnore'];
        }
        
        if (isset($config['check_ignore'])) {
            $this->checkIgnore = $config['check_ignore'];
        }
    }

    private function setSendMessageTrace($config)
    {
        if (!isset($config['send_message_trace'])) {
            return;
        }

        $this->sendMessageTrace = $config['send_message_trace'];
    }

    /**
     * Allows setting up configuration options that might be specified by class
     * name. Any interface used with `setupWithOptions` should be constructed
     * with a single parameter: an associative array with the config options.
     * It is assumed that it will be in the configuration as a sibling to the
     * key the class is named in. The options should have the same key as the
     * classname, but with 'Options' appended. E.g:
     * ```array(
     *   "sender" => "MySender",
     *   "senderOptions" => array(
     *     "speed" => 11,
     *     "protocol" => "First Contact"
     *   )
     * );```
     * Will be initialized as if you'd used:
     * `new MySender(array("speed"=>11,"protocol"=>"First Contact"));`
     * You can also just pass an instance in directly. (In which case options
     * are ignored)
     * @param $config
     * @param $keyName
     * @param $expectedType
     * @param mixed $defaultClass
     * @param bool $passWholeConfig
     */
    protected function setupWithOptions(
        $config,
        $keyName,
        $expectedType,
        $defaultClass = null,
        $passWholeConfig = false
    ) {

        $class = isset($config[$keyName]) ? $config[$keyName] : null;

        if (is_null($defaultClass) && is_null($class)) {
            return;
        }

        if (is_null($class)) {
            $class = $defaultClass;
        }
        if (is_string($class)) {
            if ($passWholeConfig) {
                $options = $config;
            } else {
                $options = isset($config[$keyName . "Options"]) ?
                            $config[$keyName . "Options"] :
                            array();
            }
            $this->$keyName = new $class($options);
        } else {
            $this->$keyName = $class;
        }

        if (!$this->$keyName instanceof $expectedType) {
            throw new \InvalidArgumentException(
                "$keyName must be a $expectedType"
            );
        }
    }

    public function logPayloadLogger()
    {
        return $this->logPayloadLogger;
    }

    public function verboseLogger()
    {
        return $this->verboseLogger;
    }

    public function getRollbarData($level, $toLog, $context)
    {
        return $this->dataBuilder->makeData($level, $toLog, $context);
    }

    public function getDataBuilder()
    {
        return $this->dataBuilder;
    }
    
    public function getLevelFactory()
    {
        return $this->levelFactory;
    }
    
    public function getSender()
    {
        return $this->sender;
    }

    public function getScrubber()
    {
        return $this->scrubber;
    }

    public function getBatched()
    {
        return $this->batched;
    }

    public function getBatchSize()
    {
        return $this->batchSize;
    }

    public function getMaxNestingDepth()
    {
        return $this->maxNestingDepth;
    }
    
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    public function getMinimumLevel()
    {
        return $this->minimumLevel;
    }
    
    public function getRaiseOnError()
    {
        return $this->raiseOnError;
    }

    /**
     * @param Payload $payload
     * @param Level $level
     * @param \Exception | \Throwable $toLog
     * @param array $context
     * @return Payload
     */
    public function transform($payload, $level, $toLog, $context)
    {
        if (count($this->custom) > 0) {
            $this->verboseLogger()->debug("Adding custom data to the payload.");
            $data = $payload->getData();
            $custom = $data->getCustom();
            $custom = array_merge(array(), $this->custom, (array)$custom);
            $data->setCustom($custom);
            $payload->setData($data);
        }
        if (is_null($this->transformer)) {
            return $payload;
        }

        $this->verboseLogger()->debug("Applying transformer " . get_class($this->transformer) . " to the payload.");

        return $this->transformer->transform($payload, $level, $toLog, $context);
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function enabled()
    {
        return $this->enabled === true;
    }
    
    public function disabled()
    {
        return !$this->enabled();
    }

    public function getSendMessageTrace()
    {
        return $this->sendMessageTrace;
    }

    public function checkIgnored($payload, $accessToken, $toLog, $isUncaught)
    {
        if (isset($this->checkIgnore)) {
            try {
                if (call_user_func($this->checkIgnore, $isUncaught, $toLog, $payload)) {
                    $this->verboseLogger()->info('Occurrence ignored due to custom check_ignore logic');
                    return true;
                }
            } catch (\Exception $exception) {
                $this->verboseLogger()->error(
                    'Exception occurred in the custom checkIgnore logic:' . $exception->getMessage()
                );
                $this->checkIgnore = null;
            }
        }
        
        if ($this->payloadLevelTooLow($payload)) {
            $this->verboseLogger()->debug("Occurrence's level is too low");
            return true;
        }

        if (!is_null($this->filter)) {
            $filter = $this->filter->shouldSend($payload, $accessToken);
            $this->verboseLogger()->debug("Custom filter result: " . var_export($filter, true));
            return $filter;
        }

        return false;
    }

    public function internalCheckIgnored($level, $toLog)
    {
        if ($this->shouldSuppress()) {
            $this->verboseLogger()->debug('Ignoring (error reporting has been disabled in PHP config)');
            return true;
        }

        if ($this->levelTooLow($this->levelFactory->fromName($level))) {
            $this->verboseLogger()->debug("Occurrence's level is too low");
            return true;
        }

        if ($toLog instanceof ErrorWrapper) {
            return $this->shouldIgnoreErrorWrapper($toLog);
        }
        
        if ($toLog instanceof \Exception) {
            return $this->shouldIgnoreException($toLog);
        }
        
        return false;
    }

    /**
     * Check if the error should be ignored due to `included_errno` config,
     * `use_error_reporting` config or `error_sample_rates` config.
     *
     * @param errno
     *
     * @return bool
     */
    public function shouldIgnoreError($errno)
    {
        if ($this->useErrorReporting && ($errno & error_reporting()) === 0) {
            // ignore due to error_reporting level
            $this->verboseLogger()->debug("Ignore (error below allowed error_reporting level)");
            return true;
        }

        if ($this->includedErrno != -1 && ($errno & $this->includedErrno) != $errno) {
            // ignore
            $this->verboseLogger()->debug("Ignore due to included_errno level");
            return true;
        }

        if (isset($this->errorSampleRates[$errno])) {
            // get a float in the range [0, 1)
            // mt_rand() is inclusive, so add 1 to mt_randmax
            $float_rand = mt_rand() / ($this->mtRandmax + 1);
            if ($float_rand > $this->errorSampleRates[$errno]) {
                // skip
                $this->verboseLogger()->debug("Skip due to error sample rating");
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if the error should be ignored due to `included_errno` config,
     * `use_error_reporting` config or `error_sample_rates` config.
     *
     * @param \Rollbar\ErrorWrapper $toLog
     *
     * @return bool
     */
    protected function shouldIgnoreErrorWrapper(ErrorWrapper $toLog)
    {
        return $this->shouldIgnoreError($toLog->errorLevel);
    }
    
    /**
     * Check if the exception should be ignored due to configured exception
     * sample rates.
     *
     * @param \Exception $toLog
     *
     * @return bool
     */
    public function shouldIgnoreException(\Exception $toLog)
    {
        // get a float in the range [0, 1)
        // mt_rand() is inclusive, so add 1 to mt_randmax
        $floatRand = mt_rand() / ($this->mtRandmax + 1);
        if ($floatRand > $this->exceptionSampleRate($toLog)) {
            // skip
            $this->verboseLogger()->debug("Skip exception due to exception sample rating");
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate what's the chance of logging this exception according to
     * exception sampling.
     *
     * @param \Exception $toLog
     *
     * @return float
     */
    public function exceptionSampleRate(\Exception $toLog)
    {
        $sampleRate = 1.0;
        if (count($this->exceptionSampleRates) == 0) {
            return $sampleRate;
        }
        
        $exceptionClasses = array();
        
        $class = get_class($toLog);
        while ($class) {
            $exceptionClasses []= $class;
            $class = get_parent_class($class);
        }
        $exceptionClasses = array_reverse($exceptionClasses);
        
        foreach ($exceptionClasses as $exceptionClass) {
            if (isset($this->exceptionSampleRates["$exceptionClass"])) {
                $sampleRate = $this->exceptionSampleRates["$exceptionClass"];
            }
        }
        
        return $sampleRate;
    }

    /**
     * @param Payload $payload
     * @return bool
     */
    private function payloadLevelTooLow($payload)
    {
        return $this->levelTooLow($payload->getData()->getLevel());
    }

    /**
     * @param Level $level
     * @return bool
     */
    private function levelTooLow($level)
    {
        return $level->toInt() < $this->minimumLevel;
    }

    private function shouldSuppress()
    {
        return error_reporting() === 0 && !$this->reportSuppressed;
    }

    public function send(EncodedPayload $payload, $accessToken)
    {
        if ($this->transmitting()) {
            $response = $this->sender->send($payload, $accessToken);
        } else {
            $response = new Response(0, "Not transmitting (transmitting disabled in configuration)");
            $this->verboseLogger()->warning($response->getInfo());
        }

        if ($this->loggingPayload()) {
            $this->logPayloadLogger()->debug(
                'Sending payload with ' . get_class($this->sender) . ":\n" .
                $payload
            );
        }

        return $response;
    }

    public function sendBatch(&$batch, $accessToken)
    {
        if ($this->transmitting()) {
            return $this->sender->sendBatch($batch, $accessToken);
        } else {
            $response = new Response(0, "Not transmitting (transmitting disabled in configuration)");
            $this->verboseLogger()->warning($response->getInfo());
            return $response;
        }
    }

    public function wait($accessToken, $max = 0)
    {
        $this->verboseLogger()->debug("Sender waiting...");
        $this->sender->wait($accessToken, $max);
    }

    public function handleResponse($payload, $response)
    {
        if (!is_null($this->responseHandler)) {
            $this->verboseLogger()->debug(
                'Applying custom response handler: ' . get_class($this->responseHandler)
            );
            $this->responseHandler->handleResponse($payload, $response);
        }
    }
}
