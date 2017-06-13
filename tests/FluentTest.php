<?php

namespace Rollbar;

use Rollbar\Payload\Level;

class FluentTest extends \PHPUnit_Framework_TestCase
{

    public function testFluent()
    {
        $socket = socket_create_listen(null);
        socket_getsockname($socket, $address, $port);

        Rollbar::init(array(
            'access_token' => 'ad865e76e7fb496fab096ac07b1dbabb',
            'environment' => 'testing'
        ), false, false, false);
        $logger = Rollbar::scope(array(
            'fluent_host' => $address,
            'fluent_port' => $port,
            'handler' => 'fluent'
        ));
        $this->assertEquals(200, $logger->log(Level::INFO, 'this is a test', array())->getStatus());

        socket_close($socket);
    }
}
