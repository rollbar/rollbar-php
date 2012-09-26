<?php
/**
 * Singleton-style wrapper around RatchetioNotifier
 *
 * Unless you need multiple RatchetioNotifier instances in the same project, use this.
 */
class Ratchetio {
    public static $instance = null;

    public static function init($config, $set_exception_handler = true, $set_error_handler = true) {
        self::$instance = new RatchetioNotifier($config);

        if ($set_exception_handler) {
            set_exception_handler('Ratchetio::report_exception');
        }
        if ($set_error_handler) {
            set_error_handler('Ratchetio::report_php_error');
        }

        if (self::$instance->batched) {
            register_shutdown_function('Ratchetio::flush');
        }
    }

    public static function report_exception($exc) {
        if (self::$instance == null) {
            return;
        }
        self::$instance->report_exception($exc);
    }

    public static function report_message($message, $level = 'error') {
        if (self::$instance == null) {
            return;
        }
        self::$instance->report_message($message, $level);
    }

    public static function report_php_error($errno, $errstr, $errfile, $errline) {
        if (self::$instance == null) {
            return;
        }
        self::$instance->report_php_error($errno, $errstr, $errfile, $errline);
    }

    public static function flush() {
        self::$instance->flush();
    }
}


class RatchetioNotifier {
    
    const VERSION = "0.2.5";

    // required
    public $access_token = '';
    
    // optional / defaults
    public $root = '';
    public $environment = 'production';
    public $branch = 'master';
    public $logger = null;
    public $base_api_url = 'https://submit.ratchet.io/api/1/';
    public $batched = true;
    public $batch_size = 50;
    public $timeout = 3;
    public $max_errno = -1;
    public $capture_error_backtraces = true;
    public $error_sample_rates = array();
    
    private $config_keys = array('access_token', 'root', 'environment', 'branch', 'logger', 
        'base_api_url', 'batched', 'batch_size', 'timeout', 'max_errno', 
        'capture_error_backtraces', 'error_sample_rates');

    // cached values for request/server data
    private $_request_data = null;
    private $_server_data = null;

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
        $levels = array(E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE,
            E_STRICT, E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED);
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
    
    public function report_message($message, $level = 'error') {
        try {
            $this->_report_message($message, $level);
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
     * Called internally when the queue exceeds $batch_size, and by Ratchetio::flush
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

    private function _report_exception($exc) {
        if (!$this->check_config()) {
            return;
        }

        $data = $this->build_base_data();

        // exception info
        $frames = $this->build_exception_frames($exc);
        $data['body'] = array(
            'trace' => array(
                'frames' => $this->build_exception_frames($exc),
                'exception' => array(
                    'class' => get_class($exc),
                    'message' => $exc->getMessage()
                )
            )
        );
        
        // request data
        $data['request'] = $this->build_request_data();
        
        // server data
        $data['server'] = $this->build_server_data();

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
        
        // request data
        $data['request'] = $this->build_request_data();
        
        // server data
        $data['server'] = $this->build_server_data();

        $payload = $this->build_payload($data);
        $this->send_payload($payload);
    }

    private function _report_message($message, $level) {
        if (!$this->check_config()) {
            return;
        }

        $data = $this->build_base_data();
        $data['level'] = strtolower($level);
        $data['body'] = array(
            'message' => array(
                'body' => $message
            )
        );
        $data['request'] = $this->build_request_data();
        $data['server'] = $this->build_server_data();

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
                'method' => $_SERVER['REQUEST_METHOD'],
            );
            
            if ($_GET) {
                $request['GET'] = $_GET;
            }
            if ($_POST) {
                $request['POST'] = $_POST;
            }
            if (isset($_SESSION) && $_SESSION) {
                $request['session'] = $_SESSION;
            }
            $this->_request_data = $request;
        }

        return $this->_request_data;
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
        $forwardfor = isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'];
        if ($forwardfor) {
            // return everything until the first comma
            $parts = explode(',', $forwardfor);
            return $parts[0];
        }
        $realip = isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'];
        if ($realip) {
            return $realip;
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    private function build_exception_frames($exc) {
        $frames = array();
        foreach ($exc->getTrace() as $frame) {
			if (empty($frame['file']))
			    $frame['file'] = $exc->getFile();

            if (empty($frame['line']))
				$frame['line'] = $exc->getLine();
			
            $frames[] = array(
                'filename' => $frame['file'],
                'lineno' => $frame['line'],
                'method' => $frame['function']
                // TODO include args? need to sanitize first.
            );
        }
        
        // add top-level file and line
        $frames[] = array(
            'filename' => $exc->getFile(),
            'lineno' => $exc->getLine()
        );

        return $frames;
    }

    private function build_error_frames($errfile, $errline) {
        $frames = array();
        
        if ($this->capture_error_backtraces) {
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
                
				if (! isset($frame['file']))
					$frame['file'] = $errfile;

				if (! isset($frame['line']))
					$frame['line'] = $errline;
				
                $frames[] = array(
                    'filename' => $frame['file'],
                    'lineno' => $frame['line'],
                    'method' => $frame['function']
                );
            }
        }

        // add top-level file and line
        $frames[] = array(
            'filename' => $errfile, 
            'lineno' => $errline
        );

        return $frames;
    }

    private function build_server_data() {
        if ($this->_server_data === null) {
            $server_data = array(
                'host' => gethostname()
            );

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

    private function build_base_data($level = 'error') {
        return array(
            'timestamp' => time(),
            'environment' => $this->environment,
            'level' => $level,
            'language' => 'php',
            'framework' => 'php',
            'notifier' => array(
                'name' => 'ratchetio-php',
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
            $this->log_warning('Got unexpected status code from Ratchet.io API ' . $action . 
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
?>
