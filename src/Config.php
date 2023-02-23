<?php declare(strict_types=1);

namespace Rollbar;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NoopHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use Rollbar\Payload\EncodedPayload;
use Rollbar\Senders\AgentSender;
use Rollbar\Senders\CurlSender;
use Rollbar\Senders\SenderInterface;
use Throwable;
use Rollbar\Senders\FluentSender;

class Config
{
    use UtilitiesTrait;

    const VERBOSE_NONE     = 'none';
    const VERBOSE_NONE_INT = 1000;

    private static array $options = array(
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
        'scrub_safelist',
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
        'raise_on_error',
        'transformer',
    );

    private string $accessToken;

    /**
     * @var boolean $enabled If this is false then do absolutely nothing,
     * try to be as close to the scenario where Rollbar did not exist at
     * all in the code.
     * Default: true
     */
    private bool $enabled = true;

    /**
     * @var boolean $transmit If this is false then we do everything except
     * make the post request at the end of the pipeline.
     * Default: true
     */
    private bool $transmit;

    /**
     * @var boolean $logPayload If this is true then we output the payload to
     * standard out or a configured logger right before transmitting.
     * Default: false
     */
    private bool $logPayload;

    /**
     * @var LoggerInterface $logPayloadLogger Logger responsible for logging request
     * payload and response dumps on. The messages logged can be controlled with
     * `log_payload` config options.
     * Default: \Monolog\Logger with \Monolog\Handler\ErrorLogHandler
     */
    private LoggerInterface $logPayloadLogger;

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
    private string $verbose;

    /**
     * @var LoggerInterface $versbosity_logger The logger object used to log
     * the internal messages of the SDK. The verbosity level of the default
     * $verbosityLogger can be controlled with `verbose` config option.
     * Default: \Rollbar\VerboseLogger
     */
    private LoggerInterface $verboseLogger;

    /**
     * @var DataBuilder
     */
    private $dataBuilder;
    private $configArray;

    /**
     * @var TransformerInterface
     */
    private ?TransformerInterface $transformer = null;
    /**
     * @var FilterInterface
     */
    private $filter;

    /**
     * @var int
     */
    private int $minimumLevel;

    /**
     * @var ResponseHandlerInterface
     */
    private $responseHandler;
    /**
     * @var \Rollbar\Senders\SenderInterface
     */
    private ?SenderInterface $sender = null;
    private $reportSuppressed;
    /**
     * @var Scrubber
     */
    private $scrubber;

    private bool $batched = false;
    private int $batchSize = 50;

    private int $maxNestingDepth = 10;

    private array $custom = array();

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

    /**
     * @var bool Sets whether to respect current {@see error_reporting()} level or not.
     */
    private bool $useErrorReporting = false;

    /**
     * @var boolean Should debug_backtrace() data be sent with string messages
     * sent through RollbarLogger::log().
     */
    private bool $sendMessageTrace = false;

    /**
     * The fully qualified class name of a custom truncation strategy class or null if no custom class is specified.
     * The class should implement {@see \Rollbar\Truncation\StrategyInterface}.
     *
     * @var string|null $customTruncation
     *
     * @since 1.6.0
     * @since 4.0.0 Added string|null type, and defaults to null.
     */
    private ?string $customTruncation = null;

    /**
     * @var boolean Should the SDK raise an exception after logging an error.
     * This is useful in test and development enviroments.
     * https://github.com/rollbar/rollbar-php/issues/448
     */
    private bool $raiseOnError = false;

    /**
     * @var int The maximum number of items reported to Rollbar within one
     * request.
     */
    private int $maxItems;

    public function __construct(array $configArray)
    {
        $this->includedErrno = Defaults::get()->includedErrno();

        $this->updateConfig($configArray);

        $this->errorSampleRates = Defaults::get()->errorSampleRates();
        if (isset($configArray['error_sample_rates'])) {
            $this->errorSampleRates = $configArray['error_sample_rates'];
        }

        $this->exceptionSampleRates = Defaults::get()->exceptionSampleRates();
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

    public static function listOptions(): array
    {
        return self::$options;
    }

    public function configure(array $config): void
    {
        $this->updateConfig($this->extend($config));
    }

    public function extend(array $config): array
    {
        return array_replace_recursive(array(), $this->configArray, $config);
    }

    public function getConfigArray(): array
    {
        return $this->configArray;
    }

    protected function updateConfig(array $config): void
    {
        $this->configArray = $config;

        $this->setEnabled($config);
        $this->setTransmit($config);
        $this->setLogPayload($config);
        $this->setLogPayloadLogger($config);
        $this->setVerbose($config);
        $this->setVerboseLogger($config);
        // The sender must be set before the access token, so we know if it is required.
        $this->setSender($config);
        $this->setAccessToken($config);
        $this->setDataBuilder($config);
        $this->setTransformer($config);
        $this->setMinimumLevel($config);
        $this->setReportSuppressed($config);
        $this->setFilters($config);
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

        $this->useErrorReporting = Defaults::get()->useErrorReporting();
        if (isset($config['use_error_reporting'])) {
            $this->useErrorReporting = $config['use_error_reporting'];
        }

        $this->maxItems = Defaults::get()->maxItems();
        if (isset($config['max_items'])) {
            $this->maxItems = $config['max_items'];
        }

        if (isset($config['custom_truncation'])) {
            $this->customTruncation = $config['custom_truncation'];
        }

        $this->customDataMethod = Defaults::get()->customDataMethod();
        if (isset($config['custom_data_method'])) {
            $this->customDataMethod = $config['custom_data_method'];
        }
    }

    private function setAccessToken(array $config): void
    {
        if (isset($_ENV['ROLLBAR_ACCESS_TOKEN']) && !isset($config['access_token'])) {
            $config['access_token'] = $_ENV['ROLLBAR_ACCESS_TOKEN'];
        }
        
        $this->utilities()->validateString(
            $config['access_token'],
            "config['access_token']",
            32,
            !$this->sender->requireAccessToken(),
        );
        $this->accessToken = $config['access_token'] ?? '';
    }

    private function setEnabled(array $config): void
    {
        if (array_key_exists('enabled', $config) && $config['enabled'] === false) {
            $this->disable();
        } else {
            if (Defaults::get()->enabled() === false) {
                $this->disable();
            } else {
                $this->enable();
            }
        }
    }

    private function setTransmit(array $config): void
    {
        $this->transmit = $config['transmit'] ?? Defaults::get()->transmit();
    }

    private function setLogPayload(array $config): void
    {
        $this->logPayload = $config['log_payload'] ?? Defaults::get()->logPayload();
    }

    private function setLogPayloadLogger(array $config): void
    {
        $this->logPayloadLogger = $config['log_payload_logger'] ??
            new Logger('rollbar.payload', array(new ErrorLogHandler()));

        if (!($this->logPayloadLogger instanceof LoggerInterface)) {
            throw new \Exception('Log Payload Logger must implement \Psr\Log\LoggerInterface');
        }
    }

    private function setVerbose(array $config): void
    {
        $this->verbose = $config['verbose'] ?? Defaults::get()->verbose();
    }

    private function setVerboseLogger(array $config): void
    {
        if (isset($config['verbose_logger'])) {
            $this->verboseLogger = $config['verbose_logger'];
        } else {
            $verboseLevel = $this->verboseInteger();
            // The verboseLogger must be an instance of LoggerInterface. Setting
            // it to null would require every log call to check if it is null,
            // so we set it to a NoopHandler instead. The NoopHandler does what
            // you would expect and does nothing.
            //
            // Additionally, since Monolog v3 all log levels are defined in an
            // enum. This means that using a custom log level will throw an
            // exception. To avoid this we only set the level if it is not our
            // "custom" verbose level.
            //
            // Using a built-in level would cause the verbose logger to log
            // messages that are currently silent if the verbose log leve is set
            // to "none".
            if ($verboseLevel === self::VERBOSE_NONE_INT) {
                $handler = new NoopHandler();
            } else {
                $handler = new ErrorLogHandler();
                $handler->setLevel($verboseLevel);
            }
            $this->verboseLogger = new Logger('rollbar.verbose', array($handler));
        }

        if (!($this->verboseLogger instanceof LoggerInterface)) {
            throw new \Exception('Verbose logger must implement \Psr\Log\LoggerInterface');
        }
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    private function setDataBuilder(array $config): void
    {
        if (!isset($config['utilities'])) {
            $config['utilities'] = $this->utilities();
        }

        $exp = DataBuilderInterface::class;
        $def = DataBuilder::class;
        $this->setupWithOptions($config, "dataBuilder", $exp, $def, true);
    }

    private function setTransformer(array $config): void
    {
        $expected = TransformerInterface::class;
        $this->setupWithOptions($config, "transformer", $expected);
    }

    private function setMinimumLevel(array $config): void
    {
        $this->minimumLevel = \Rollbar\Defaults::get()->minimumLevel();

        $override = $config['minimum_level'] ?? null;
        $override = array_key_exists('minimumLevel', $config) ? $config['minimumLevel'] : $override;

        if ($override instanceof Level) {
            $this->minimumLevel = $override->toInt();
        } elseif (is_string($override)) {
            $level = LevelFactory::fromName($override);
            if ($level !== null) {
                $this->minimumLevel = $level->toInt();
            }
        } elseif (is_int($override)) {
            $this->minimumLevel = $override;
        }
    }

    private function setReportSuppressed(array $config): void
    {
        $this->reportSuppressed = isset($config['reportSuppressed']) && $config['reportSuppressed'];
        if (!$this->reportSuppressed) {
            $this->reportSuppressed = isset($config['report_suppressed']) && $config['report_suppressed'];
        }

        if (!$this->reportSuppressed) {
            $this->reportSuppressed = \Rollbar\Defaults::get()->reportSuppressed();
        }
    }

    private function setFilters(array $config): void
    {
        $this->setupWithOptions($config, "filter", FilterInterface::class);
    }

    private function setSender(array $config): void
    {
        $expected = SenderInterface::class;
        $default = CurlSender::class;

        $this->setTransportOptions($config);
        $default = $this->setAgentSenderOptions($config, $default);
        $default = $this->setFluentSenderOptions($config, $default);

        $this->setupWithOptions($config, "sender", $expected, $default);
    }

    private function setScrubber(array $config): void
    {
        $exp = ScrubberInterface::class;
        $def = Scrubber::class;
        $this->setupWithOptions($config, "scrubber", $exp, $def, true);
    }

    private function setBatched(array $config): void
    {
        if (array_key_exists('batched', $config)) {
            $this->batched = $config['batched'];
        }
    }

    private function setRaiseOnError(array $config): void
    {
        if (array_key_exists('raise_on_error', $config)) {
            $this->raiseOnError = $config['raise_on_error'];
        } else {
            $this->raiseOnError = \Rollbar\Defaults::get()->raiseOnError();
        }
    }

    private function setBatchSize(array $config): void
    {
        if (array_key_exists('batch_size', $config)) {
            $this->batchSize = $config['batch_size'];
        }
    }

    private function setMaxNestingDepth(array $config): void
    {
        if (array_key_exists('max_nesting_depth', $config)) {
            $this->maxNestingDepth = $config['max_nesting_depth'];
        }
    }

    public function setCustom(array $config): void
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

    public function transmitting(): bool
    {
        return $this->transmit;
    }

    public function loggingPayload()
    {
        return $this->logPayload;
    }

    public function verbose(): string
    {
        return $this->verbose;
    }

    public function verboseInteger(): int
    {
        if ($this->verbose == self::VERBOSE_NONE) {
            return self::VERBOSE_NONE_INT;
        }
        /**
         * @psalm-suppress UndefinedClass
         * @psalm-suppress UndefinedDocblockClass
         * @var int|\Monolog\Level $level Monolog v2 returns an integer, v3 returns a \Monolog\Level enum.
         */
        $level = Logger::toMonologLevel($this->verbose);
        /**
         * @psalm-suppress UndefinedClass
         */
        if (is_a($level, '\Monolog\Level')) {
            return $level->value;
        }
        return $level;
    }

    public function getCustom()
    {
        return $this->dataBuilder->getCustom();
    }

    /**
     * Sets the custom truncation strategy class for payloads.
     *
     * @param string|null $type The fully qualified class name of the custom payload truncation strategy. The class
     *                          must implement {@see \Rollbar\Truncation\StrategyInterface}. If null is given any custom
     *                          strategy will be removed.
     *
     * @return void
     */
    public function setCustomTruncation(?string $type): void
    {
        $this->customTruncation = $type;
    }

    /**
     * Returns the fully qualified class name of the custom payload truncation strategy.
     *
     * @return string|null Will return null if a custom truncation strategy was not defined.
     *
     * @since 1.6.0
     * @since 4.0.0 Added may return null.
     */
    public function getCustomTruncation(): ?string
    {
        return $this->customTruncation;
    }

    private function setTransportOptions(array &$config): void
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

    private function setAgentSenderOptions(array &$config, mixed $default): mixed
    {
        if (!array_key_exists('handler', $config) || $config['handler'] != 'agent') {
            return $default;
        }
        $default = AgentSender::class;
        if (array_key_exists('agent_log_location', $config)) {
            $config['senderOptions'] = array(
                'agentLogLocation' => $config['agent_log_location']
            );
        }
        return $default;
    }

    private function setFluentSenderOptions(array &$config, mixed $default): mixed
    {
        if (!isset($config['handler']) || $config['handler'] != 'fluent') {
            return $default;
        }
        $default = FluentSender::class;

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

    private function setResponseHandler(array $config): void
    {
        $this->setupWithOptions($config, "responseHandler", ResponseHandlerInterface::class);
    }

    private function setCheckIgnoreFunction(array $config): void
    {
        // Remain backwards compatible
        if (isset($config['checkIgnore'])) {
            $this->checkIgnore = $config['checkIgnore'];
        }

        if (isset($config['check_ignore'])) {
            $this->checkIgnore = $config['check_ignore'];
        }
    }

    private function setSendMessageTrace(array $config): void
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
     * @param array $config
     * @param string $keyName
     * @param string $expectedType
     * @param ?string $defaultClass
     * @param bool $passWholeConfig
     */
    protected function setupWithOptions(
        array $config,
        string $keyName,
        string $expectedType,
        ?string $defaultClass = null,
        bool $passWholeConfig = false
    ): void {

        $class = $config[$keyName] ?? null;

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
                $options = $config[$keyName . "Options"] ?? array();
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

    /**
     * Returns the logger responsible for logging request payload and response dumps, if enabled.
     *
     * @return LoggerInterface
     */
    public function logPayloadLogger(): LoggerInterface
    {
        return $this->logPayloadLogger;
    }

    public function verboseLogger(): LoggerInterface
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

    public function getSender()
    {
        return $this->sender;
    }

    public function getScrubber()
    {
        return $this->scrubber;
    }

    public function getBatched(): bool
    {
        return $this->batched;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getMaxNestingDepth(): int
    {
        return $this->maxNestingDepth;
    }

    public function getMaxItems(): int
    {
        return $this->maxItems;
    }

    public function getMinimumLevel(): int
    {
        return $this->minimumLevel;
    }

    public function getRaiseOnError(): bool
    {
        return $this->raiseOnError;
    }

    public function transform(
        Payload $payload,
        Level|string $level,
        mixed $toLog,
        array $context = array ()
    ): Payload {
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

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function enabled(): bool
    {
        return $this->enabled === true;
    }

    public function disabled(): bool
    {
        return !$this->enabled();
    }

    public function getSendMessageTrace()
    {
        return $this->sendMessageTrace;
    }


    public function checkIgnored(Payload $payload, $toLog, bool $isUncaught)
    {
        if (isset($this->checkIgnore)) {
            try {
                $ok = ($this->checkIgnore)($isUncaught, $toLog, $payload);
                if ($ok) {
                    $this->verboseLogger()->info('Occurrence ignored due to custom check_ignore logic');
                    return true;
                }
            } catch (Throwable $exception) {
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
            $filter = $this->filter->shouldSend($payload, $isUncaught);
            $this->verboseLogger()->debug("Custom filter result: " . var_export($filter, true));
            return $filter;
        }

        return false;
    }

    public function internalCheckIgnored(string $level, mixed $toLog): bool
    {
        if ($this->shouldSuppress()) {
            $this->verboseLogger()->debug('Ignoring (error reporting has been disabled in PHP config)');
            return true;
        }

        if ($this->levelTooLow(LevelFactory::fromName($level))) {
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
     * Check if the error should be ignored due to `includedErrno` config, `useErrorReporting` config or
     * `errorSampleRates` config.
     *
     * @param int $errno The PHP error level bitmask.
     *
     * @return bool
     */
    public function shouldIgnoreError(int $errno): bool
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
    protected function shouldIgnoreErrorWrapper(ErrorWrapper $toLog): bool
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
    public function shouldIgnoreException(\Exception $toLog): bool
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
    public function exceptionSampleRate(\Exception $toLog): float
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
    private function payloadLevelTooLow(Payload $payload): bool
    {
        return $this->levelTooLow($payload->getData()->getLevel());
    }

    /**
     * @param Level $level
     * @return bool
     */
    private function levelTooLow(Level $level): bool
    {
        return $level->toInt() < $this->minimumLevel;
    }

    /**
     * Decides if a given log message should be suppressed by policy.
     * If so, then a debug message is emitted: "Ignoring (error reporting has been disabled in PHP config"
     * @since 3.0.1
     */
    public function shouldSuppress(): bool
    {
        // report_suppressed option forces reporting regardless of PHP settings.
        if ($this->reportSuppressed) {
            return false;
        }

        $errorReporting = error_reporting();

        // For error control operator of PHP 8:
        // > Prior to PHP 8.0.0, the error_reporting() called inside the
        // > custom error handler always returned 0 if the error was
        // > suppressed by the @ operator. As of PHP 8.0.0, it returns
        // > the value E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR |
        // > E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE.
        // https://www.php.net/manual/en/language.operators.errorcontrol.php
        if (version_compare(PHP_VERSION, '8.0', 'ge') && $errorReporting === (
            E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE
        )) {
            return true;
        }

        // PHP 7 or manually disabled case:
        return $errorReporting === 0;
    }

    public function send(EncodedPayload $payload, string $accessToken): ?Response
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

    public function sendBatch(array $batch, string $accessToken): ?Response
    {
        if ($this->transmitting()) {
            $this->sender->sendBatch($batch, $accessToken);
            return null;
        } else {
            $response = new Response(0, "Not transmitting (transmitting disabled in configuration)");
            $this->verboseLogger()->warning($response->getInfo());
            return $response;
        }
    }

    public function wait(string $accessToken, $max = 0): void
    {
        $this->verboseLogger()->debug("Sender waiting...");
        $this->sender->wait($accessToken, $max);
    }

    public function handleResponse(Payload $payload, Response $response): void
    {
        if (!is_null($this->responseHandler)) {
            $this->verboseLogger()->debug(
                'Applying custom response handler: ' . get_class($this->responseHandler)
            );
            $this->responseHandler->handleResponse($payload, $response);
        }
    }
}
