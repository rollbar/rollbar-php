<?php
/**
 * \Rollbar\VerboseLogger is a class used as the internal verbosity logger
 * for the SDK. A custom class was needed to support level
 * \Rollbar\Config::VERBOSE_NONE in the `verbose` config option which
 * makes the logger completely quiet.
 * 
 * Using this logger, if the SDK is configured with 
 * `verbose` == \Rollbar\Config::VERBOSE_NONE any logging calls like
 * $verboseLogger->info("Message") will be ignored.
 * 
 * @package \Rollbar
 * @author Artur Moczulski <artur@elevatedwebapps.com>
 * @author Rollbar, Inc.
 */
namespace Rollbar;

class VerboseLogger extends \Monolog\Logger
{
    private $config;

    public function __construct($name, $rollbarConfig, array $handlers = array(), array $processors = array())
    {
        $this->config = $rollbarConfig;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * @param  mixed   $level   The log level. Supported log level are 
     * \Psr\Log\LogLevel plus \Rollbar\Config::VERBOSE_NONE
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = array())
    {
        if ($this->config->verbose() == Config::VERBOSE_NONE) {
            return false;
        }

        return parent::addRecord($level, $message, $context);
    }
}