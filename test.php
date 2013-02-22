<pre>
<?php
require 'rollbar.php';

class EchoLogger {
    public function log($level, $message) {
        echo "[Rollbar] $level $message\n";
    }
}

function throw_test_exception($val) {
    throw new Exception("other test exception");
}

function get_current_person() {
    return array('id' => 2, 'username' => 'brian2');
}

function main() {
    $config = array(
        //'access_token' => '089b0e7847134faf9ed9d3febd3f6d46',
        //'access_token' => 'eb6b9dad914343d7a4231421a75c8458',
        'access_token' => 'fdcc9f0eeecf4a90adccc6ef49e1805c',
        'environment' => 'php-test',
        'root' => '/Users/brian/www/rollbar-php',
        'base_api_url' => 'http://brian.rollbardev.com/api/1/',
        'logger' => new EchoLogger(),
        'error_sample_rates' => array(
            E_NOTICE => 0.5,
            E_USER_ERROR => 1,
            E_USER_NOTICE => 0.5
        ),
        /*'person' => array(
            'idgarbage' => '1',
            'username' => 'brian',
            'email' => 'brianrue@gmail.com'
        ),*/
        'person_fn' => 'get_current_person'
    );
    // $config, $set_exception_handler, $set_error_handler
    Rollbar::init($config, true, true);
    
    try {
        throw_test_exception("yo");
    } catch (Exception $e) {
        Rollbar::report_exception($e);
    }

    Rollbar::report_message("hey there", "info");
    
    trigger_error("test user error", E_USER_ERROR);
    trigger_error("test user warning", E_USER_WARNING);
    trigger_error("test user notice", E_USER_NOTICE);
    
    // raises an E_NOTICE, reported by the error handler
    $foo = $bar2;

    // reported by the exception handler
    throw new Exception("uncaught exception");

}

main();

?>
</pre>
