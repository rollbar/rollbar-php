<?php
/**
 * Singleton-style wrapper around RollbarNotifier
 *
 * Unless you need multiple RollbarNotifier instances in the same project, use this.
 */
class Rollbar {
    /** @var RollbarNotifier */
    public static $instance = null;

    public static function init($config, $set_exception_handler = true, $set_error_handler = true) {
        self::$instance = new RollbarNotifier($config);

        if ($set_exception_handler) {
            set_exception_handler('Rollbar::report_exception');
        }
        if ($set_error_handler) {
            set_error_handler('Rollbar::report_php_error');
        }

        if (self::$instance->batched) {
            register_shutdown_function('Rollbar::flush');
        }
    }

    public static function report_exception($exc) {
        if (self::$instance == null) {
            return;
        }
        self::$instance->report_exception($exc);
    }

    public static function report_message($message, $level = 'error', $extra_data = null) {
        if (self::$instance == null) {
            return;
        }
        self::$instance->report_message($message, $level, $extra_data);
    }

    public static function report_php_error($errno, $errstr, $errfile, $errline) {
        if (self::$instance != null) {
            self::$instance->report_php_error($errno, $errstr, $errfile, $errline);
        }
        return false;
    }

    public static function flush() {
        // Catch any fatal errors that are causing the shutdown
        $last_error = error_get_last();
        if (!is_null($last_error)) {
            switch($last_error['type']) {
                case E_ERROR:
                    self::$instance->report_php_error($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
                    break;
            }
        }
        self::$instance->flush();
    }
}

class_alias('Rollbar', 'Ratchetio');

class RollbarNotifier {

    const VERSION = "0.5.0";

    // required
    public $access_token = '';

    // optional / defaults
    public $base_api_url = 'https://api.rollbar.com/api/1/';
    public $batch_size = 50;
    public $batched = true;
    public $branch = 'master';
    public $capture_error_backtraces = true;
    public $environment = 'production';
    public $error_sample_rates = array();
    public $host = null;
    /** @var iRollbarLogger */
    public $logger = null;
    public $max_errno = 1024;  // ignore E_STRICT and above
    public $person = null;
    public $person_fn = null;
    public $root = '';
    public $scrub_fields = array('passwd', 'password', 'secret', 'confirm_password', 'password_confirmation');
    public $shift_function = true;
    public $timeout = 3;

    private $config_keys = array('access_token', 'base_api_url', 'batch_size', 'batched', 'branch', 
        'capture_error_backtraces', 'environment', 'error_sample_rates', 'host', 'logger', 
        'max_errno', 'person', 'person_fn', 'root', 'scrub_fields', 'shift_function', 'timeout');

    // cached values for request/server/person data
    private $_request_data = null;
    private $_server_data = null;
    private $_person_data = null;

    // payload queue, used when $batched is true
    private $_queue = array();

    private $_mt_randmax;

    public function __construct($config) {
        foreach ($this->config_keys as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }

        if (!$this->access_token) {
            $this->log_error('Missing access token');
        }

        // fill in missing values in error_sample_rates
        $levels = array(E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING,
            E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR);

        // PHP 5.3.0
        if (defined('E_DEPRECATED')) {
            $levels = array_merge($levels, array(E_DEPRECATED, E_USER_DEPRECATED));
        }

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

    public function report_exception($exc) {
        try {
            $this->_report_exception($exc);
        } catch (Exception $e) {
            try {
                $this->log_error("Exception while reporting exception");
            } catch (Exception $e) {
                // swallow
            }
        }
    }

    public function report_message($message, $level = 'error', $extra_data = null) {
        try {
            $this->_report_message($message, $level, $extra_data);
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
            $this->_report_php_error($errno, $errstr, $errfile, $errline);
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
        $queue_size = count($this->_queue);
        if ($queue_size > 0) {
            $this->log_info('Flushing queue of size ' . $queue_size);
            $this->send_batch($this->_queue);
            $this->_queue = array();
        }
    }

    /**
     * @param $exc Exception
     */
    private function _report_exception($exc) {
        if (!$this->check_config()) {
            return;
        }

        $data = $this->build_base_data();

        // exception info
        $data['body'] = array(
            'trace' => array(
                'frames' => $this->build_exception_frames($exc),
                'exception' => array(
                    'class' => get_class($exc),
                    'message' => $exc->getMessage()
                )
            )
        );

        // request, server, person data
        $data['request'] = $this->build_request_data();
        $data['server'] = $this->build_server_data();
        $data['person'] = $this->build_person_data();

        $payload = $this->build_payload($data);
        $this->send_payload($payload);
    }

    private function _report_php_error($errno, $errstr, $errfile, $errline) {
        if (!$this->check_config()) {
            return;
        }

        if ($this->max_errno != -1 && $errno >= $this->max_errno) {
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
        $level = 'info';
        $constant = '#' . $errno;
        switch ($errno) {
            case 1:
                $level = 'error';
                $constant = 'E_ERROR';
                break;
            case 2:
                $level = 'warning';
                $constant = 'E_WARNING';
                break;
            case 8:
                $level = 'info';
                $constant = 'E_NOTICE';
                break;
            case 256:
                $level = 'error';
                $constant = 'E_USER_ERROR';
                break;
            case 512:
                $level = 'warning';
                $constant = 'E_USER_WARNING';
                break;
            case 1024:
                $level = 'info';
                $constant = 'E_USER_NOTICE';
                break;
            case 2048:
                $level = 'info';
                $constant = 'E_STRICT';
                break;
            case 4096:
                $level = 'error';
                $constant = 'E_RECOVERABLE_ERROR';
                break;
            case 8192:
                $level = 'info';
                $constant = 'E_DEPRECATED';
                break;
            case 16384:
                $level = 'info';
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

        $payload = $this->build_payload($data);
        $this->send_payload($payload);
    }

    private function _report_message($message, $level, $extra_data) {
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

        $payload = $this->build_payload($data);
        $this->send_payload($payload);
    }

    private function check_config() {
        return $this->access_token && strlen($this->access_token) == 32;
    }

    private function build_request_data() {
        if ($this->_request_data === null) {
            $request = array(
                'url' => $this->current_url(),
                'user_ip' => $this->user_ip(),
                'headers' => $this->headers(),
                'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null,
            );

            if ($_GET) {
                $request['GET'] = $_GET;
            }
            if ($_POST) {
                $request['POST'] = $this->scrub_request_params($_POST);
            }
            if (isset($_SESSION) && $_SESSION) {
                $request['session'] = $_SESSION;
            }
            $this->_request_data = $request;
        }

        return $this->_request_data;
    }
    
    private function scrub_request_params($params) {
        foreach ($params as $k => $v) {
            if (in_array($k, $this->scrub_fields)) {
                $params[$k] = str_repeat('*', strlen($v));
            }
        }
        
        return $params;
    }

    private function headers() {
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
            // serializes to emtpy json object
            return new stdClass;
        }
    }

    private function current_url() {
        // should work with apache. not sure about other environments.
        $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
        $host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'unknown';
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        $url = $proto . '://' . $host;

        if (($proto == 'https' && $port != 443) || ($proto == 'http' && $port != 80)) {
            $url .= ':' . $port;
        }

        $url .= $path;

        return $url;
    }

    private function user_ip() {
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
     * @param $exc Exception
     * @return array
     */
    private function build_exception_frames($exc) {
        $frames = array();
        foreach ($exc->getTrace() as $frame) {
            $frames[] = array(
                'filename' => isset($frame['file']) ? $frame['file'] : '<internal>',
                'lineno' =>  isset($frame['line']) ? $frame['line'] : 0,
                'method' => $frame['function']
                // TODO include args? need to sanitize first.
            );
        }

        // rollbar expects most recent call to be last, not first
        $frames = array_reverse($frames);

        // add top-level file and line to end of the reversed array
        $frames[] = array(
            'filename' => $exc->getFile(),
            'lineno' => $exc->getLine()
        );

        $this->shift_method($frames);

        return $frames;
    }

    private function shift_method(&$frames) {
        if ($this->shift_function) {
            // shift 'method' values down one frame, so they reflect where the call
            // occurs (like Rollbar expects), instead of what is being called.
            for ($i = count($frames) - 1; $i > 0; $i--) {
                $frames[$i]['method'] = $frames[$i - 1]['method'];
            }
            $frames[0]['method'] = '<main>';
        }
    }

    private function build_error_frames($errfile, $errline) {
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

                $frames[] = array(
                    // Sometimes, file and line are not set. See:
                    // http://stackoverflow.com/questions/4581969/why-is-debug-backtrace-not-including-line-number-sometimes
                    'filename' => isset($frame['file']) ? $frame['file'] : "<internal>",
                    'lineno' =>  isset($frame['line']) ? $frame['line'] : 0,
                    'method' => $frame['function']
                );
            }

            // rollbar expects most recent call last, not first
            $frames = array_reverse($frames);

            // add top-level file and line to end of the reversed array
            $frames[] = array(
                'filename' => $errfile,
                'lineno' => $errline
            );

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

    private function build_server_data() {
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

    private function build_person_data() {
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

    private function build_base_data($level = 'error') {
        return array(
            'timestamp' => time(),
            'environment' => $this->environment,
            'level' => $level,
            'language' => 'php',
            'framework' => 'php',
            'notifier' => array(
                'name' => 'rollbar-php',
                'version' => self::VERSION
            )
        );
    }

    private function build_payload($data) {
        return array(
            'access_token' => $this->access_token,
            'data' => $data
        );
    }

    private function send_payload($payload) {
        if ($this->batched) {
            if (count($this->_queue) >= $this->batch_size) {
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
    private function _send_payload($payload) {
        $this->log_info("Sending payload");

        $post_data = json_encode($payload);
        $this->make_api_call('item', $post_data);
    }

    /**
     * Sends a batch of payloads to the /batch endpoint.
     * A batch is just an array of standalone payloads.
     * $batch - php array of payloads
     */
    private function send_batch($batch) {
        $this->log_info("Sending batch");

        $post_data = json_encode($batch);
        $this->make_api_call('item_batch', $post_data);
    }

    private function make_api_call($action, $post_data) {
        $url = $this->base_api_url . $action . '/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
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

    private function log_info($msg) {
        $this->log_message("INFO", $msg);
    }

    private function log_warning($msg) {
        $this->log_message("WARNING", $msg);
    }

    private function log_error($msg) {
        $this->log_message("ERROR", $msg);
    }

    private function log_message($level, $msg) {
        if ($this->logger !== null) {
            $this->logger->log($level, $msg);
        }
    }
}

interface iRollbarLogger {
    public function log($level, $msg);
}
