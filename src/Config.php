<?php namespace Rollbar;

use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;

class Config
{
    private $accessToken;
    /**
     * @var DataBuilder
     */
    private $dataBuilder;
    private $configArray;
    /**
     * @var TransformerInterface
     */
    private $transformer;
    /**
     * @var FilterInterface
     */
    private $filter;
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

    public function __construct(array $configArray)
    {
        $this->updateConfig($configArray);
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

    protected function updateConfig($c)
    {
        $this->configArray = $c;

        $this->setAccessToken($c);
        $this->setDataBuilder($c);
        $this->setTransformer($c);
        $this->setMinimumLevel($c);
        $this->setReportSuppressed($c);
        $this->setFilters($c);
        $this->setSender($c);
        $this->setResponseHandler($c);
    }

    private function setAccessToken($c)
    {
        if (isset($_ENV['ROLLBAR_ACCESS_TOKEN']) && !isset($config['access_token'])) {
            $config['access_token'] = $_ENV['ROLLBAR_ACCESS_TOKEN'];
        }
        Utilities::validateString($c['access_token'], "config['access_token']", 32, false);
        $this->accessToken = $c['access_token'];
    }

    private function setDataBuilder($c)
    {
        $exp = "Rollbar\DataBuilderInterface";
        $def = "Rollbar\DataBuilder";
        $this->setupWithOptions($c, "dataBuilder", $exp, $def, true);
    }

    private function setTransformer($c)
    {
        $expected = "Rollbar\TransformerInterface";
        $this->setupWithOptions($c, "transformer", $expected);
    }

    private function setMinimumLevel($c)
    {
        if (!isset($c['minimumLevel']) || is_null($c['minimumLevel'])) {
            $this->minimumLevel = 0;
        } elseif ($c['minimumLevel'] instanceof Level) {
            $this->minimumLevel = $c['minimumLevel']->toInt();
        } elseif (is_string($c['minimumLevel'])) {
            $level = Level::fromName($c['minimumLevel']);
            if ($level !== null) {
                $this->minimumLevel = $level->toInt();
            }
        } elseif (is_int($c['minimumLevel'])) {
            $this->minimumLevel = $c['minimumLevel'];
        } else {
            $this->minimumLevel = 0;
        }
    }

    private function setReportSuppressed($c)
    {
        $this->reportSuppressed = isset($c['reportSuppressed']) && $c['reportSuppressed'];
    }

    private function setFilters($c)
    {
        $this->setupWithOptions($c, "filter", "Rollbar\FilterInterface");
    }

    private function setSender($c)
    {
        $expected = "Rollbar\Senders\SenderInterface";
        $default = "Rollbar\Senders\CurlSender";

        if (array_key_exists('handler', $c) && $c['handler'] == 'agent') {
            $default = "Rollbar\Senders\AgentSender";
            if (array_key_exists('agent_log_location', $c)) {
                $c['senderOptions'] = array(
                    'agentLogLocation' => $c['agent_log_location']
                );
            }
        }
        $this->setupWithOptions($c, "sender", $expected, $default);
    }

    private function setResponseHandler($c)
    {
        $this->setupWithOptions($c, "responseHandler", "Rollbar\ResponseHandlerInterface");
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
     * @param $c
     * @param $keyName
     * @param $expectedType
     * @param mixed $defaultClass
     * @param bool $passWholeConfig
     */
    protected function setupWithOptions(
        $c,
        $keyName,
        $expectedType,
        $defaultClass = null,
        $passWholeConfig = false
    ) {
        $$keyName = isset($c[$keyName]) ? $c[$keyName] : null;

        if (is_null($defaultClass) && is_null($$keyName)) {
            return;
        }

        if (is_null($$keyName)) {
            $$keyName = $defaultClass;
        }
        if (is_string($$keyName)) {
            if ($passWholeConfig) {
                $options = $c;
            } else {
                $options = isset($c[$keyName . "Options"]) ? $c[$keyName . "Options"] : array();
            }
            $this->$keyName = new $$keyName($options);
        } else {
            $this->$keyName = $$keyName;
        }

        if (!$this->$keyName instanceof $expectedType) {
            throw new \InvalidArgumentException("$keyName must be a $expectedType");
        }
    }

    public function getRollbarData($level, $toLog, $context)
    {
        return $this->dataBuilder->makeData($level, $toLog, $context);
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
        if (is_null($this->transformer)) {
            return $payload;
        }
        return $this->transformer->transform($payload, $level, $toLog, $context);
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function checkIgnored($payload, $accessToken)
    {
        if ($this->shouldSupppress()) {
            return true;
        }
        if ($this->levelTooLow($payload)) {
            return true;
        }
        if (!is_null($this->filter)) {
            return $this->filter->shouldSend($payload, $accessToken);
        }
        return false;
    }

    /**
     * @param Payload $payload
     * @return bool
     */
    private function levelTooLow($payload)
    {
        return $payload->getData()->getLevel()->toInt() < $this->minimumLevel;
    }

    private function shouldSupppress()
    {
        return error_reporting() === 0 && !$this->reportSuppressed;
    }

    public function send($payload, $accessToken)
    {
        return $this->sender->send($payload, $accessToken);
    }

    public function handleResponse($payload, $response)
    {
        if (!is_null($this->responseHandler)) {
            $this->responseHandler->handleResponse($payload, $response);
        }
    }
}
