<?php

class RollbarTest extends PHPUnit_Framework_TestCase {

    public function testInit() {
        Rollbar::init(array('access_token' => 'THE_TOKEN', 'environment' => 'THE_ENV'));

        $this->assertEquals('THE_TOKEN', Rollbar::$instance->access_token);
        $this->assertEquals('THE_ENV', Rollbar::$instance->environment);
    }

}

?>
