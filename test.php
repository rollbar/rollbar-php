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
    $ratchet = new Ratchet($config);
    
    try {
        throw_test_exception("yo");
    } catch (Exception $e) {
        $ratchet->report_exception($e);
    }

    $ratchet->report_message("hey there", "info");
}

main();

?>
</pre>
