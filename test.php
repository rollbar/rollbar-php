<pre>
<?php
require 'Ratchet.php';

class EchoLogger {
    public function log($level, $message) {
        echo "[Ratchet] $level $message\n";
    }
}

function throw_test_exception($val) {
    throw new Exception("other test exception");
}

function main() {
    $config = array(
        'access_token' => '089b0e7847134faf9ed9d3febd3f6d46',
        'root' => '/Users/brian/www/ratchet-php',
        'endpoint' => 'http://brian.ratchetdev.com/api/1/item/',
        'logger' => new EchoLogger()
    );
    // $config, $set_exception_handler, $set_error_handler
    Ratchet::init($config, true, true);
    
    try {
        throw_test_exception("yo");
    } catch (Exception $e) {
        Ratchet::report_exception($e);
    }

    Ratchet::report_message("hey there", "info");

    // raises an E_NOTICE, reported by the error handler
    $foo = $bar;

    // reported by the exception handler
    throw new Exception("uncaught exception");
}

main();

?>
</pre>
