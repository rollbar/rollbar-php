<?php

namespace Rollbar;

use ReflectionClass;
use ReflectionException;
use Rollbar\Payload\Level;
use Rollbar\Senders\CurlSender;

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

    /**
     * This test will fail if the {@see CurlSender::$ipResolve} property is renamed or removed.
     */
    public function testIPResolve(): void
    {
        $sender = new CurlSender([
            'ip_resolve' => CURL_IPRESOLVE_V4,
        ]);
        self::assertSame(CURL_IPRESOLVE_V4, self::getPrivateProperty($sender, 'ipResolve'));

        $sender = new CurlSender([]);
        self::assertSame(CURL_IPRESOLVE_V4, self::getPrivateProperty($sender, 'ipResolve'));

        $sender = new CurlSender([
            'ip_resolve' => CURL_IPRESOLVE_V6,
        ]);
        self::assertSame(CURL_IPRESOLVE_V6, self::getPrivateProperty($sender, 'ipResolve'));

        $sender = new CurlSender([
            'ip_resolve' => CURL_IPRESOLVE_WHATEVER,
        ]);
        self::assertSame(CURL_IPRESOLVE_WHATEVER, self::getPrivateProperty($sender, 'ipResolve'));
    }

    /**
     * Returns the value of a private property of an object.
     *
     * @param object $object   The object from which to get the private property.
     * @param string $property The name of the private property to get.
     * @return mixed
     * @throws ReflectionException If the object or property does not exist.
     */
    private static function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
