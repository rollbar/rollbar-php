<?php

namespace Rollbar;

use Rollbar\Payload\Level;

class CurlSenderTest extends BaseRollbarTest
{
    
    public function testCurlError()
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "endpoint" => "fake-endpoint"
        ));
        $response = $logger->log(Level::WARNING, "Testing PHP Notifier", array());

        $this->assertContains(
            $response->getInfo(),
            array(
              "Could not resolve host: fake-endpointitem",
              "Empty reply from server"
            )
        );
    }
}
