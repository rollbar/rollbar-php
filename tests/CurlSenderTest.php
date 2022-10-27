<?php

namespace Rollbar;

use Rollbar\Payload\Level;

class CurlSenderTest extends BaseRollbarTest
{
    
    public function testCurlError(): void
    {
        $logger = new RollbarLogger(array(
            "access_token" => $this->getTestAccessToken(),
            "environment" => "testing-php",
            "endpoint" => "fake-endpoint"
        ));
        $response = $logger->report(Level::WARNING, "Testing PHP Notifier", array());

        $this->assertContains(
            $response->getInfo(),
            array(
                "Couldn't resolve host 'fake-endpointitem'", // hack for PHP 5.3
                "Could not resolve host: fake-endpointitem",
                "Could not resolve: fake-endpointitem (Domain name not found)",
                "Empty reply from server"
            )
        );
    }
}
