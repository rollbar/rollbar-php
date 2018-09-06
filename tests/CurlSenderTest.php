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
        
        $this->assertTrue(
            in_array(
                $response->getInfo(),
                array(
                    "Couldn't resolve host 'fake-endpointitem'", // hack for PHP 5.3
                    "Could not resolve host: fake-endpointitem",
                    "Empty reply from server"
                )
            )
        );
    }
}
