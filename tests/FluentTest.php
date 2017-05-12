<?php

namespace Rollbar;

use Psr\Log\LogLevel;

class FluentTest extends \PHPUnit_Framework_TestCase
{

    public function testFluent()
    {
        $socket = socket_create_listen(null);
        socket_getsockname($socket, $address, $port);

        Rollbar::init(array(
            'access_token' => 'ad865e76e7fb496fab096ac07b1dbabb',
            'environment' => 'testing',
            'fluent_host' => $address,
            'fluent_port' => $port,
            'handler' => 'fluent',
        ), false, false, false);
        $logger = Rollbar::logger();
        $this->assertEquals(200, $logger->log(LogLevel::INFO, 'this is a test', array())->getStatus());

        socket_close($socket);
    }
}
