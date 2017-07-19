<?php

namespace Rollbar;

use Rollbar\Payload\Level;

class CurlSenderTest extends BaseRollbarTest
{
    
    public function testCurlError()
    {
        $l = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "endpoint" => "fake-endpoint"
        ));
        $response = $l->log(Level::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(
            "Could not resolve host: fake-endpointitem",
            $response->getInfo()
        );
    }
}
