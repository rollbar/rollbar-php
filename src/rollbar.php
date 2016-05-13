<?php

namespace Rollbar;



class Rollbar {

    /**
     * @var RollbarNotifier
     */
    public static $instance = null;

    /**
     * Initialize
     *
     */
    public static function init($config = array(), $set_exception_handler = true, $set_error_handler = true, $report_fatal_errors = true) {
        // Heroku support
        // Use env vars for configuration, if set
        if (isset($_ENV['ROLLBAR_ACCESS_TOKEN']) && !isset($config['access_token'])) {
            $config['access_token'] = $_ENV['ROLLBAR_ACCESS_TOKEN'];
        }
        if (isset($_ENV['ROLLBAR_ENDPOINT']) && !isset($config['endpoint'])) {
            $config['endpoint'] = $_ENV['ROLLBAR_ENDPOINT'];
        }
        if (isset($_ENV['HEROKU_APP_DIR']) && !isset($config['root'])) {
            $config['root'] = $_ENV['HEROKU_APP_DIR'];
        }

        self::$instance = new RollbarNotifier($config);

        if ($set_exception_handler) {
            set_exception_handler('Rollbar::report_exception');
        }
        if ($set_error_handler) {
            set_error_handler('Rollbar::report_php_error');
        }
        if ($report_fatal_errors) {
            register_shutdown_function('Rollbar::report_fatal_error');
        }

        if (self::$instance->batched) {
            register_shutdown_function('Rollbar::flush');
        }
    }

    public static function report_exception($exc, $extra_data = null, $payload_data = null) {
        if (self::$instance == null) {
            return;
        }
        return self::$instance->report_exception($exc, $extra_data, $payload_data);
    }

    public static function report_message($message, $level = Level::ERROR, $extra_data = null, $payload_data = null) {
        if (self::$instance == null) {
            return;
        }
        return self::$instance->report_message($message, $level, $extra_data, $payload_data);
    }

    public static function report_fatal_error() {
        // Catch any fatal errors that are causing the shutdown
        $last_error = error_get_last();
        if (!is_null($last_error)) {
            switch ($last_error['type']) {
                case E_PARSE:
                case E_ERROR:
                    self::$instance->report_php_error($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
                    break;
            }
        }
    }

    // This function must return false so that the default php error handler runs
    public static function report_php_error($errno, $errstr, $errfile, $errline) {
        if (self::$instance != null) {
            self::$instance->report_php_error($errno, $errstr, $errfile, $errline);
        }
        return false;
    }

    public static function flush() {
        self::$instance->flush();
    }
}

// Send errors that have these levels
if (!defined('ROLLBAR_INCLUDED_ERRNO_BITMASK')) {
    define('ROLLBAR_INCLUDED_ERRNO_BITMASK', E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
}

