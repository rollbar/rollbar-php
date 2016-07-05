<?php
include 'Level.php';

/**
 * Singleton-style wrapper around RollbarNotifier
 *
 * Unless you need multiple RollbarNotifier instances in the same project, use this.
 */
if ( !defined( 'BASE_EXCEPTION' ) ) {
	define( 'BASE_EXCEPTION', version_compare( phpversion(), '7.0', '<' )? '\Exception': '\Throwable' );
}

class Rollbar {
    /** @var RollbarNotifier */
    public static $instance = null;

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
        if (self::$instance == null) {
            return;
        }
        self::$instance->flush();
    }
}

class RollbarException {
    private $message;
    private $exception;

    /**
     * RollbarException constructor.
     * @param string $message
     * @param Exception | Error $exception
     */
    public function __construct($message, $exception = null) {
        $this->message = $message;
        $this->exception = $exception;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getException() {
        return $this->exception;
    }
}

// Send errors that have these levels
if (!defined('ROLLBAR_INCLUDED_ERRNO_BITMASK')) {
    define('ROLLBAR_INCLUDED_ERRNO_BITMASK', E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
}

class RollbarNotifier {
    const VERSION = "0.18.2";

    // required
    public $access_token = '';

    // optional / defaults
    public $base_api_url = 'https://api.rollbar.com/api/1/';
    public $batch_size = 50;
    public $batched = true;
    public $branch = null;
    public $capture_error_backtraces = true;
    public $code_version = null;
    public $environment = 'production';
    public $error_sample_rates = array();
    // available handlers: blocking, agent
    public $handler = 'blocking';
    public $agent_log_location = '/var/tmp';
    public $host = null;
    /** @var iRollbarLogger */
    public $logger = null;
    public $included_errno = ROLLBAR_INCLUDED_ERRNO_BITMASK;
    public $person = null;
    public $person_fn = null;
    public $root = '';
    public $checkIgnore = null;
    public $scrub_fields = array('passwd', 'pass', 'password', 'secret', 'confirm_password',
        'password_confirmation', 'auth_token', 'csrf_token');
    public $shift_function = true;
    public $timeout = 3;
    public $report_suppressed = false;
    public $use_error_reporting = false;
    public $proxy = null;
    public $include_error_code_context = false;
    public $include_exception_code_context = false;
    public $enable_utf8_sanitization = true;

    private $config_keys = array('access_token', 'base_api_url', 'batch_size', 'batched', 'branch',
        'capture_error_backtraces', 'code_version', 'environment', 'error_sample_rates', 'handler',
        'agent_log_location', 'host', 'logger', 'included_errno', 'person', 'person_fn', 'root', 'checkIgnore',
        'scrub_fields', 'shift_function', 'timeout', 'report_suppressed', 'use_error_reporting', 'proxy',
        'include_error_code_context', 'include_exception_code_context', 'enable_utf8_sanitization');

    // cached values for request/server/person data
    private $_php_context = null;
    private $_request_data = null;
    private $_server_data = null;
    private $_person_data = null;

    // payload queue, used when $batched is true
    private $_queue = array();

    // file handle for agent log
    private $_agent_log = null;

    private $_iconv_available = null;

    private $_mt_randmax;

    private $_curl_ipresolve_supported;

    /** @var iSourceFileReader $_source_file_reader */
    private $_source_file_reader;

    public function __construct($config) {
        foreach ($this->config_keys as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }
        $this->_source_file_reader = new SourceFileReader();

        if (!$this->access_token && $this->handler != 'agent') {
            $this->log_error('Missing access token');
        }

        // fill in missing values in error_sample_rates
        $levels = array(E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING,
            E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR);

        // PHP 5.3.0
        if (defined('E_DEPRECATED')) {
            $levels = array_merge($levels, array(E_DEPRECATED, E_USER_DEPRECATED));
        }

        // PHP 5.3.0
        $this->_curl_ipresolve_supported = defined('CURLOPT_IPRESOLVE');

        $curr = 1;
        for ($i = 0, $num = count($levels); $i < $num; $i++) {
            $level = $levels[$i];
            if (isset($this->error_sample_rates[$level])) {
                $curr = $this->error_sample_rates[$level];
            } else {
                $this->error_sample_rates[$level] = $curr;
            }
        }

        // cache this value
        $this->_mt_randmax = mt_getrandmax();
    }

    public function report_exception($exc, $extra_data = null, $payload_data = null) {
        try {
            if ( !is_a( $exc, BASE_EXCEPTION ) ) {
                throw new Exception(sprintf('Report exception requires an instance of %s.', BASE_EXCEPTION ));
            }

            return $this->_report_exception($exc, $extra_data, $payload_data);
        } catch (Exception $e) {
            try {
                $this->log_error("Exception while reporting exception");
            } catch (Exception $e) {
                // swallow
            }
        }
    }

    public function report_message($message, $level = Level::ERROR, $extra_data = null, $payload_data = null) {
        try {
            return $this->_report_message($message, $level, $extra_data, $payload_data);
        } catch (Exception $e) {
            try {
                $this->log_error("Exception while reporting message");
            } catch (Exception $e) {
                // swallow
            }
        }
    }

    public function report_php_error($errno, $errstr, $errfile, $errline) {
        try {
            return $this->_report_php_error($errno, $errstr, $errfile, $errline);
        } catch (Exception $e) {
            try {
                $this->log_error("Exception while reporting php error");
            } catch (Exception $e) {
                // swallow
            }
        }
    }

    /**
     * Flushes the queue.
     * Called internally when the queue exceeds $batch_size, and by Rollbar::flush
     * on shutdown.
     */
    public function flush() {
        $queue_size = $this->queueSize();
        if ($queue_size > 0) {
            $this->log_info('Flushing queue of size ' . $queue_size);
            $this->send_batch($this->_queue);
            $this->_queue = array();
        }
    }

    /**
     * Returns the current queue size.
     */
    public function queueSize() {
        return count($this->_queue);
    }

    /**
     * Run the checkIgnore function and determine whether to send the Exception to the API or not.
     *
     * @param  bool             $isUncaught
     * @param  RollbarException $exception
     * @param  array            $payload    Data being sent to the API
     * @return bool
     */
    protected function _shouldIgnore($isUncaught, RollbarException $exception, array $payload)
    {
        try {
            if (is_callable($this->checkIgnore)
                && call_user_func_array($this->checkIgnore, array($isUncaught,$exception,$payload))
            ) {
                $this->log_info('This item was not sent to Rollbar because it was ignored. '
                    . 'This can happen if a custom checkIgnore() function was used.');

                return true;
            }
        } catch (Exception $e) {
            // Disable the custom checkIgnore and report errors in the checkIgnore function
            $this->checkIgnore = null;
            $this->log_error("Removing custom checkIgnore(). Error while calling custom checkIgnore function:\n"
                . $e->getMessage());
        }

        return false;
    }

    /**
     * @param \Throwable|\Exception $exc
     * @param mixed $extra_data
     * @param mixed$payload_data
     * @return string the uuid of the occurrence
     */
    protected function _report_exception( $exc, $extra_data = null, $payload_data = null) {
        if (!$this->check_config()) {
            return;
        }

        if (error_reporting() === 0 && !$this->report_suppressed) {
            // ignore
            return;
        }

        $data = $this->build_base_data();

        $trace_chain = $this->build_exception_trace_chain($exc, $extra_data);

        if (count($trace_chain) > 1) {
            $data['body']['trace_chain'] = $trace_chain;
        } else {
            $data['body']['trace'] = $trace_chain[0];
        }

        // request, server, person data
        if ('http' === $this->_php_context) {
            $data['request'] = $this->build_request_data();
        }
        $data['server'] = $this->build_server_data();
        $data['person'] = $this->build_person_data();

        // merge $payload_data into $data
        // (overriding anything already present)
        if ($payload_data !== null && is_array($payload_data)) {
            foreach ($payload_data as $key => $val) {
                $data[$key] = $val;
            }
        }

        $data = $this->_sanitize_keys($data);
        array_walk_recursive($data, array($this, '_sanitize_utf8'));

        $payload = $this->build_payload($data);

        // Determine whether to send the request to the API.
        if ($this->_shouldIgnore(true, new RollbarException($exc->getMessage(), $exc), $payload)) {
            return;
        }

        $this->send_payload($payload);

        return $data['uuid'];
    }

    protected function _sanitize_utf8(&$value) {
        if (!$this->enable_utf8_sanitization)
            return;

        if (!isset($this->_iconv_available)) {
            $this->_iconv_available = function_exists('iconv');
        }
        if (is_string($value) && $this->_iconv_available) {
            $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        }
    }

    protected function _sanitize_keys(array $data) {
        $response = array();
        foreach ($data as $key => $value) {
            $this->_sanitize_utf8($key);
            if (is_array($value)) {
                $response[$key] = $this->_sanitize_keys($value);
            } else {
                $response[$key] = $value;
            }
        }

        return $response;
    }

    protected function _report_php_error($errno, $errstr, $errfile, $errline) {
        if (!$this->check_config()) {
            return;
        }

        if (error_reporting() === 0 && !$this->report_suppressed) {
            // ignore
            return;
        }

        if ($this->use_error_reporting && (error_reporting() & $errno) === 0) {
            // ignore
            return;
        }

        if ($this->included_errno != -1 && ($errno & $this->included_errno) != $errno) {
            // ignore
            return;
        }

        if (isset($this->error_sample_rates[$errno])) {
            // get a float in the range [0, 1)
            // mt_rand() is inclusive, so add 1 to mt_randmax
            $float_rand = mt_rand() / ($this->_mt_randmax + 1);
            if ($float_rand > $this->error_sample_rates[$errno]) {
                // skip
                return;
            }
        }

        $data = $this->build_base_data();

        // set error level and error constant name
        $level = Level::INFO;
        $constant = '#' . $errno;
        switch ($errno) {
            case 1:
                $level = Level::ERROR;
                $constant = 'E_ERROR';
                break;
            case 2:
                $level = Level::WARNING;
                $constant = 'E_WARNING';
                break;
            case 4:
                $level = Level::CRITICAL;
                $constant = 'E_PARSE';
		break;
            case 8:
                $level = Level::INFO;
                $constant = 'E_NOTICE';
                break;
            case 256:
                $level = Level::ERROR;
                $constant = 'E_USER_ERROR';
                break;
            case 512:
                $level = Level::WARNING;
                $constant = 'E_USER_WARNING';
                break;
            case 1024:
                $level = Level::INFO;
                $constant = 'E_USER_NOTICE';
                break;
            case 2048:
                $level = Level::INFO;
                $constant = 'E_STRICT';
                break;
            case 4096:
                $level = Level::ERROR;
                $constant = 'E_RECOVERABLE_ERROR';
                break;
            case 8192:
                $level = Level::INFO;
                $constant = 'E_DEPRECATED';
                break;
            case 16384:
                $level = Level::INFO;
                $constant = 'E_USER_DEPRECATED';
                break;
        }
        $data['level'] = $level;

        // use the whole $errstr. may want to split this by colon for better de-duping.
        $error_class = $constant . ': ' . $errstr;

        // build something that looks like an exception
        $data['body'] = array(
            'trace' => array(
                'frames' => $this->build_error_frames($errfile, $errline),
                'exception' => array(
                    'class' => $error_class
                )
            )
        );

        // request, server, person data
        $data['request'] = $this->build_request_data();
        $data['server'] = $this->build_server_data();
        $data['person'] = $this->build_person_data();

        array_walk_recursive($data, array($this, '_sanitize_utf8'));

        $payload = $this->build_payload($data);

        // Determine whether to send the request to the API.
        $exception = new ErrorException($error_class, 0, $errno, $errfile, $errline);
        if ($this->_shouldIgnore(true, new RollbarException($exception->getMessage(), $exception), $payload)) {
            return;
        }

        $this->send_payload($payload);

        return $data['uuid'];
    }

    protected function _report_message($message, $level, $extra_data, $payload_data) {
        if (!$this->check_config()) {
            return;
        }

        $data = $this->build_base_data();
        $data['level'] = strtolower($level);

        $message_obj = array('body' => $message);
        if ($extra_data !== null && is_array($extra_data)) {
            // merge keys from $extra_data to $message_obj
            foreach ($extra_data as $key => $val) {
                if ($key == 'body') {
                    // rename to 'body_' to avoid clobbering
                    $key = 'body_';
                }
                $message_obj[$key] = $val;
            }
        }
        $data['body']['message'] = $message_obj;

        $data['request'] = $this->build_request_data();
        $data['server'] = $this->build_server_data();
        $data['person'] = $this->build_person_data();

        // merge $payload_data into $data
        // (overriding anything already present)
        if ($payload_data !== null && is_array($payload_data)) {
            foreach ($payload_data as $key => $val) {
                $data[$key] = $val;
            }
        }

        array_walk_recursive($data, array($this, '_sanitize_utf8'));

        $payload = $this->build_payload($data);

        // Determine whether to send the request to the API.
        if ($this->_shouldIgnore(true, new RollbarException($message), $payload)) {
            return;
        }

        $this->send_payload($payload);

        return $data['uuid'];
    }

    protected function check_config() {
        return $this->handler == 'agent' || ($this->access_token && strlen($this->access_token) == 32);
    }

    protected function build_request_data() {
        if ($this->_request_data === null) {
            $request = array(
                'url' => $this->scrub_url($this->current_url()),
                'user_ip' => $this->user_ip(),
                'headers' => $this->headers(),
                'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null,
            );

            if ($_GET) {
                $request['GET'] = $this->scrub_request_params($_GET);
            }
            if ($_POST) {
                $request['POST'] = $this->scrub_request_params($_POST);
            }
            if (isset($_SESSION) && $_SESSION) {
                $request['session'] = $this->scrub_request_params($_SESSION);
            }
            $this->_request_data = $request;
        }

        return $this->_request_data;
    }

    protected function scrub_url($url) {
        $url_query = parse_url($url, PHP_URL_QUERY);
        if (!$url_query) return $url;
        parse_str($url_query, $parsed_output);
        // using x since * requires URL-encoding
        $scrubbed_params = $this->scrub_request_params($parsed_output, 'x');
        $scrubbed_url = str_replace($url_query, http_build_query($scrubbed_params), $url);
        return $scrubbed_url;
    }

    protected function scrub_request_params($params, $replacement = '*') {
        $scrubbed = array();
        $potential_regex_filters = array_filter($this->scrub_fields, function($field) {
            return strpos($field, '/') === 0;
        });
        foreach ($params as $k => $v) {
            if ($this->_key_should_be_scrubbed($k, $potential_regex_filters)) {
                $scrubbed[$k] = $this->_scrub($v, $replacement);
            } elseif (is_array($v)) {
                // recursively handle array params
                $scrubbed[$k] = $this->scrub_request_params($v, $replacement);
            } else {
                $scrubbed[$k] = $v;
            }
        }

        return $scrubbed;
    }

    protected function _key_should_be_scrubbed($key, $potential_regex_filters) {
        if (in_array(strtolower($key), $this->scrub_fields, true)) return true;
        foreach ($potential_regex_filters as $potential_regex) {
            if (@preg_match($potential_regex, $key)) return true;
        }
        return false;
    }

    protected function _scrub($value, $replacement = '*') {
        $count = is_array($value) ? count($value) : strlen($value);
        return str_repeat($replacement, $count);
    }

    protected function headers() {
        $headers = array();
        foreach ($this->scrub_request_params($_SERVER) as $key => $val) {
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
            // serializes to emtpy json object
            return new stdClass;
        }
    }

    protected function current_url() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
        } else if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $proto = 'https';
        } else {
            $proto = 'http';
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else if (!empty($_SERVER['HTTP_HOST'])) {
            $parts = explode(':', $_SERVER['HTTP_HOST']);
            $host = $parts[0];
        } else if (!empty($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        } else {
            $host = 'unknown';
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $port = $_SERVER['HTTP_X_FORWARDED_PORT'];
        } else if (!empty($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        } else if ($proto === 'https') {
            $port = 443;
        } else {
            $port = 80;
        }

        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        $url = $proto . '://' . $host;

        if (($proto == 'https' && $port != 443) || ($proto == 'http' && $port != 80)) {
            $url .= ':' . $port;
        }

        $url .= $path;

        return $url;
    }

    protected function user_ip() {
        $forwardfor = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;
        if ($forwardfor) {
            // return everything until the first comma
            $parts = explode(',', $forwardfor);
            return $parts[0];
        }
        $realip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : null;
        if ($realip) {
            return $realip;
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * @param \Throwable|\Exception $exc
     * @param mixed $extra_data
     * @return array
     */
    protected function build_exception_trace($exc, $extra_data = null)
    {
        $message = $exc->getMessage();

        $trace = array(
            'frames' => $this->build_exception_frames($exc),
            'exception' => array(
                'class' => get_class($exc),
                'message' => !empty($message) ? $message : 'unknown',
            ),
        );

        if ($extra_data !== null) {
            $trace['extra'] = $extra_data;
        }

        return $trace;
    }

    /**
     * @param \Throwable|\Exception $exc
     * @param array $extra_data
     * @return array
     */
    protected function build_exception_trace_chain( $exc, $extra_data = null)
    {
        $chain = array();
        $chain[] = $this->build_exception_trace($exc, $extra_data);

        $previous = $exc->getPrevious();

        while ( is_a( $previous, BASE_EXCEPTION ) ) {
            $chain[] = $this->build_exception_trace($previous);
            $previous = $previous->getPrevious();
        }

        return $chain;
    }

    /**
     * @param \Throwable|\Exception $exc
     * @return array
     */
    protected function build_exception_frames($exc) {
        $frames = array();

        foreach ($exc->getTrace() as $frame) {
            $framedata = array(
                'filename' => isset($frame['file']) ? $frame['file'] : '<internal>',
                'lineno' =>  isset($frame['line']) ? $frame['line'] : 0,
                'method' => $frame['function']
                // TODO include args? need to sanitize first.
            );
            if($this->include_exception_code_context && isset($frame['file']) && isset($frame['line'])) {
                $this->add_frame_code_context($frame['file'], $frame['line'], $framedata);
            }
            $frames[] = $framedata;
        }

        // rollbar expects most recent call to be last, not first
        $frames = array_reverse($frames);

        // add top-level file and line to end of the reversed array
        $file = $exc->getFile();
        $line = $exc->getLine();
        $framedata = array(
            'filename' => $file,
            'lineno' => $line
        );
        if($this->include_exception_code_context) {
            $this->add_frame_code_context($file, $line, $framedata);
        }
        $frames[] = $framedata;

        $this->shift_method($frames);

        return $frames;
    }

    protected function shift_method(&$frames) {
        if ($this->shift_function) {
            // shift 'method' values down one frame, so they reflect where the call
            // occurs (like Rollbar expects), instead of what is being called.
            for ($i = count($frames) - 1; $i > 0; $i--) {
                $frames[$i]['method'] = $frames[$i - 1]['method'];
            }
            $frames[0]['method'] = '<main>';
        }
    }

    protected function build_error_frames($errfile, $errline) {
        if ($this->capture_error_backtraces) {
            $frames = array();
            $backtrace = debug_backtrace();
            foreach ($backtrace as $frame) {
                // skip frames in this file
                if (isset($frame['file']) && $frame['file'] == __FILE__) {
                    continue;
                }
                // skip the confusing set_error_handler frame
                if ($frame['function'] == 'report_php_error' && count($frames) == 0) {
                    continue;
                }

                $framedata = array(
                    // Sometimes, file and line are not set. See:
                    // http://stackoverflow.com/questions/4581969/why-is-debug-backtrace-not-including-line-number-sometimes
                    'filename' => isset($frame['file']) ? $frame['file'] : "<internal>",
                    'lineno' =>  isset($frame['line']) ? $frame['line'] : 0,
                    'method' => $frame['function']
                );
                if($this->include_error_code_context && isset($frame['file']) && isset($frame['line'])) {
                    $this->add_frame_code_context($frame['file'], $frame['line'], $framedata);
                }
                $frames[] = $framedata;
            }

            // rollbar expects most recent call last, not first
            $frames = array_reverse($frames);

            // add top-level file and line to end of the reversed array
            $framedata = array(
                'filename' => $errfile,
                'lineno' => $errline
            );
            if($this->include_error_code_context) {
                $this->add_frame_code_context($errfile, $errline, $framedata);
            }
            $frames[] = $framedata;

            $this->shift_method($frames);

            return $frames;
        } else {
            return array(
                array(
                    'filename' => $errfile,
                    'lineno' => $errline
                )
            );
        }
    }

    protected function build_server_data() {
        if ($this->_server_data === null) {
            $server_data = array();

            if ($this->host === null) {
                // PHP 5.3.0
                if (function_exists('gethostname')) {
                    $this->host = gethostname();
                } else {
                    $this->host = php_uname('n');
                }
            }
            $server_data['host'] = $this->host;
            $server_data['argv'] = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;

            if ($this->branch) {
                $server_data['branch'] = $this->branch;
            }
            if ($this->root) {
                $server_data['root'] = $this->root;
            }
            $this->_server_data = $server_data;
        }
        return $this->_server_data;
    }

    protected function build_person_data() {
        // return cached value if non-null
        // it *is* possible for it to really be null (i.e. user is not logged in)
        // but we'll keep trying anyway until we get a logged-in user value.
        if ($this->_person_data == null) {
            // first priority: try to use $this->person
            if ($this->person && is_array($this->person)) {
                if (isset($this->person['id'])) {
                    $this->_person_data = $this->person;
                    return $this->_person_data;
                }
            }

            // second priority: try to use $this->person_fn
            if ($this->person_fn && is_callable($this->person_fn)) {
                $data = @call_user_func($this->person_fn);
                if (isset($data['id'])) {
                    $this->_person_data = $data;
                    return $this->_person_data;
                }
            }
        } else {
            return $this->_person_data;
        }

        return null;
    }

    protected function build_base_data($level = Level::ERROR) {
        if (null === $this->_php_context) {
            $this->_php_context = $this->get_php_context();
        }

        $data = array(
            'timestamp' => time(),
            'environment' => $this->environment,
            'level' => $level,
            'language' => 'php',
            'framework' => 'php',
            'php_context' => $this->_php_context,
            'notifier' => array(
                'name' => 'rollbar-php',
                'version' => self::VERSION
            ),
            'uuid' => $this->uuid4()
        );

        if ($this->code_version) {
            $data['code_version'] = $this->code_version;
        }

        return $data;
    }

    protected function build_payload($data) {
        $payload = array(
            'data' => $data
        );

        if ($this->access_token) {
            $payload['access_token'] = $this->access_token;
        }

        return $payload;
    }

    protected function send_payload($payload) {
        if ($this->batched) {
            if ($this->queueSize() >= $this->batch_size) {
                // flush queue before adding payload to queue
                $this->flush();
            }
            $this->_queue[] = $payload;
        } else {
            $this->_send_payload($payload);
        }
    }

    /**
     * Sends a single payload to the /item endpoint.
     * $payload - php array
     */
    protected function _send_payload($payload) {
        if ($this->handler == 'agent') {
            $this->_send_payload_agent($payload);
        } else {
            $this->_send_payload_blocking($payload);
        }
    }

    protected function _send_payload_blocking($payload) {
        $this->log_info("Sending payload");
        $access_token = $payload['access_token'];
        $post_data = json_encode($payload);
        $this->make_api_call('item', $access_token, $post_data);
    }

    protected function _send_payload_agent($payload) {
        // Only open this the first time
        if (empty($this->_agent_log)) {
            $this->load_agent_file();
        }
        $this->log_info("Writing payload to file");
        fwrite($this->_agent_log, json_encode($payload) . "\n");
    }

    /**
     * Sends a batch of payloads to the /batch endpoint.
     * A batch is just an array of standalone payloads.
     * $batch - php array of payloads
     */
    protected function send_batch($batch) {
        if ($this->handler == 'agent') {
            $this->send_batch_agent($batch);
        } else {
            $this->send_batch_blocking($batch);
        }
    }

    protected function send_batch_agent($batch) {
        $this->log_info("Writing batch to file");

        // Only open this the first time
        if (empty($this->_agent_log)) {
            $this->load_agent_file();
        }

        foreach ($batch as $item) {
            fwrite($this->_agent_log, json_encode($item) . "\n");
        }
    }

    protected function send_batch_blocking($batch) {
        $this->log_info("Sending batch");
        $access_token = $batch[0]['access_token'];
        $post_data = json_encode($batch);
        $this->make_api_call('item_batch', $access_token, $post_data);
    }

    protected function get_php_context() {
        return php_sapi_name() === 'cli' || defined('STDIN') ? 'cli' : 'http';
    }

    protected function make_api_call($action, $access_token, $post_data) {
        $url = $this->base_api_url . $action . '/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Rollbar-Access-Token: ' . $access_token));

        if ($this->proxy) {
            $proxy = is_array($this->proxy) ? $this->proxy : array('address' => $this->proxy);

            if (isset($proxy['address'])) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy['address']);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            }

            if (isset($proxy['username']) && isset($proxy['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);
            }
        }

        if ($this->_curl_ipresolve_supported) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status_code != 200) {
            $this->log_warning('Got unexpected status code from Rollbar API ' . $action .
                ': ' .$status_code);
            $this->log_warning('Output: ' .$result);
        } else {
            $this->log_info('Success');
        }
    }

    /* Logging */

    protected function log_info($msg) {
        $this->log_message("INFO", $msg);
    }

    protected function log_warning($msg) {
        $this->log_message("WARNING", $msg);
    }

    protected function log_error($msg) {
        $this->log_message("ERROR", $msg);
    }

    protected function log_message($level, $msg) {
        if ($this->logger !== null) {
            $this->logger->log($level, $msg);
        }
    }

    // from http://www.php.net/manual/en/function.uniqid.php#94959
    protected function uuid4() {
        mt_srand();
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

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
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    protected function load_agent_file() {
        $this->_agent_log = fopen($this->agent_log_location . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar', 'a');
    }

    protected function add_frame_code_context($file, $line, array &$framedata) {
        $source = $this->get_source_file_reader()->read_as_array($file);
        if (is_array($source)) {
            $source = str_replace(array("\n", "\t", "\r"), '', $source);
            $total = count($source);
            $line = $line - 1;
            $framedata['code'] = $source[$line];
            $offset = 6;
            $min = max($line - $offset, 0);
            if ($min !== $line) {
                $framedata['context']['pre'] = array_slice($source, $min, $line - $min);
            }
            $max = min($line + $offset, $total);
            if ($max !== $line) {
                $framedata['context']['post'] = array_slice($source, $line + 1, $max - $line);
            }
        }
    }

    protected function get_source_file_reader() { return $this->_source_file_reader; }
}

interface iRollbarLogger {
    public function log($level, $msg);
}

class Ratchetio extends Rollbar {}

interface iSourceFileReader {

    /**
     * @param string $file_path
     * @return string[]
     */
    public function read_as_array($file_path);
}

class SourceFileReader implements iSourceFileReader {

    public function read_as_array($file_path) { return file($file_path); }
}
