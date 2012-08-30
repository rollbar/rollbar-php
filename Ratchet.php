<?php

/**
 * Singleton-style wrapper around RatchetNotifier
 *
 * Unless you need multiple Ratchet instances in the same app, use this.
 */
class Ratchet {
    public static $instance = null;

    public static function init($config, $set_exception_handler = true, $set_error_handler = true) {
        self::$instance = new RatchetNotifier($config);

        if ($set_exception_handler) {
            set_exception_handler('Ratchet::report_exception');
        }
        if ($set_error_handler) {
            set_error_handler('Ratchet::report_php_error');
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
}


class RatchetNotifier {
    
    const DEFAULT_ENDPOINT = 'https://submit.ratchet.io/api/1/item/';
    const VERSION = "0.1";

    // required
    public $access_token = '';
    
    // optional / defaults
    public $root = '';
    public $environment = 'production';
    public $branch = 'master';
    public $logger = null;
    public $endpoint = self::DEFAULT_ENDPOINT;
    private $config_keys = array('access_token', 'root', 'environment', 'branch', 'logger', 
        'endpoint');
    
    public function __construct($config) {
        foreach ($this->config_keys as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }

        if (!$this->access_token) {
            $this->log_error('Missing access token');
        }
    }

    private function check_config() {
        return $this->access_token && strlen($this->access_token) == 32;
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

    private function _report_exception($exc) {
        if (!$this->check_config()) {
            return;
        }

        $data = $this->build_base_data();

        // exception info
        $frames = $this->build_frames($exc);
        $data['body'] = array(
            'trace' => array(
                'frames' => $this->build_frames($exc),
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
        }

        // use the whole $errstr. may want to split this by colon for better de-duping.
        $error_class = $constant . ' ' . $errstr;

        // build something that looks like an exception
        $data['body'] = array(
            'trace' => array(
                'frames' => array(array('filename' => $errfile, 'lineno' => $errline)),
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

    private function build_request_data() {
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

        return $request;
    }

    private function headers() {
        $headers = array();
        foreach ($_SERVER as $key => $val) {
            if (substr($key, 0, 5) == 'HTTP_') {
                // convert HTTP_CONTENT_TYPE to Content-Type
                $name = strtolower(substr($key, 5));
                if (strpos($name, "_") != -1) {
                    $name = preg_replace("/ /", "-", ucwords(preg_replace('/_/', " ", $name)));
                }
                $headers[$name] = $val;
            }
        }
        return $headers;
    }

    private function current_url() {
        // should work with apache. not sure about other environments.
        $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        $path = $_SERVER['REQUEST_URI'];

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
            $parts = preg_split(',', $forwardfor);
            return $parts[0];
        }
        $realip = isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'];
        if ($realip) {
            return $realip;
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    private function build_frames($exc) {
        $frames = array();
        foreach ($exc->getTrace() as $frame) {
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

    private function build_server_data() {
        $server_data = array(
            'host' => gethostname()
        );

        if ($this->branch) {
            $server_data['branch'] = $this->branch;
        }
        if ($this->root) {
            $server_data['root'] = $this->root;
        }

        return $server_data;
    }

    private function build_base_data($level = 'error') {
        return array(
            'timestamp' => time(),
            'environment' => $this->environment,
            'level' => $level,
            'language' => 'php',
            'notifier' => array(
                'name' => 'ratchet-php',
                'version' => self::VERSION
            )
        );
    }

    private function build_payload($data) {
        $payload = array(
            'access_token' => $this->access_token,
            'data' => $data
        );
        return json_encode($payload);
    }

    private function send_payload($payload) {
        $this->log_info("Sending payload");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status_code != 200) {
            $this->log_warning("Got unexpected status code from Ratchet API: $status_code");
            $this->log_warning("Output: $result");
        } else {
            $this->log_info("Success");
        }
    }

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
