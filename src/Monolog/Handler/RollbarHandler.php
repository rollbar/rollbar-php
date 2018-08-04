<?php

/*
 * !!! DEPRECATED AS OF 8/4/2018 !!!
 * 
 * Please, do not use this class anymore to use Rollbar with Monolog anymore.
 * The Monolog library includes a dedicated handler now here:
 * https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/RollbarHandler.php
 *
 * Using Monolog's handler is the recommended approach when using Rollbar
 * with Monolog.
 */

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollbar\Monolog\Handler;

use Rollbar\RollbarLogger;
use Throwable;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Sends errors to Rollbar
 *
 * If the context data contains a `payload` key, that is used as an array
 * of payload options to RollbarLogger's log method.
 *
 * Rollbar's context info will contain the context + extra keys from the log record
 * merged, and then on top of that a few keys:
 *
 *  - level (rollbar level name)
 *  - monolog_level (monolog level name, raw level, as rollbar only has 5 but monolog 8)
 *  - channel
 *  - datetime (unix timestamp)
 *
 * @author Paul Statezny <paulstatezny@gmail.com>
 */
class RollbarHandler extends AbstractProcessingHandler
{
    /**
     * @var RollbarLogger
     */
    protected $rollbarLogger;

    protected $levelMap = array(
        Logger::DEBUG     => 'debug',
        Logger::INFO      => 'info',
        Logger::NOTICE    => 'info',
        Logger::WARNING   => 'warning',
        Logger::ERROR     => 'error',
        Logger::CRITICAL  => 'critical',
        Logger::ALERT     => 'critical',
        Logger::EMERGENCY => 'critical',
    );

    /**
     * Records whether any log records have been added since the last flush of the rollbar notifier
     *
     * @var bool
     */
    private $hasRecords = false;

    protected $initialized = false;

    /**
     * @param RollbarLogger   $rollbarLogger   RollbarLogger object constructed with valid token
     * @param int             $level           The minimum logging level at which this handler will be triggered
     * @param bool            $bubble          Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(RollbarLogger $rollbarLogger, $level = Logger::ERROR, $bubble = true)
    {
        $this->rollbarLogger = $rollbarLogger;

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (!$this->initialized) {
            // __destructor() doesn't get called on Fatal errors
            register_shutdown_function(array($this, 'close'));
            $this->initialized = true;
        }

        $context = $record['context'];
        $context = array_merge($context, $record['extra'], array(
            'level' => $this->levelMap[$record['level']],
            'monolog_level' => $record['level_name'],
            'channel' => $record['channel'],
            'datetime' => $record['datetime']->format('U'),
        ));

        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $exception = $context['exception'];
            unset($context['exception']);
            $toLog = $exception;
        } else {
            $toLog = $record['message'];
        }
        
        $this->rollbarLogger->log($context['level'], $toLog, $context);

        $this->hasRecords = true;
    }

    public function flush()
    {
        if ($this->hasRecords) {
            $this->rollbarLogger->flush();
            $this->hasRecords = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->flush();
    }
}
