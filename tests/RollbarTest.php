<?php

class RollbarTest extends PHPUnit_Framework_TestCase {

    public function testInit() {
        Rollbar::init(array('access_token' => 'THE_TOKEN', 'environment' => 'THE_ENV'));

        $this->assertEquals(Rollbar::$instance->access_token, 'THE_TOKEN');
        $this->assertEquals(Rollbar::$instance->environment, 'THE_ENV');
    }

}

?>
