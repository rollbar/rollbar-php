<?php namespace Rollbar;

use Rollbar\Response;

class ResponseTest extends BaseRollbarTest
{
    public function testStatus(): void
    {
        $r = new Response(200, array("whatever"=>5));
        $this->assertEquals(200, $r->getStatus());
    }

    public function testInfo(): void
    {
        $r = new Response(200, "FAKE INFO");
        $this->assertEquals("FAKE INFO", $r->getInfo());
    }

    public function testUuid(): void
    {
        $r = new Response(200, "FAKE INFO", "abc123");
        $this->assertEquals("abc123", $r->getUuid());
    }

    public function testWasSuccessful(): void
    {
        $response = new Response(200, null);
        $this->assertTrue($response->wasSuccessful());
        $response = new Response(199, null);
        $this->assertFalse($response->wasSuccessful());
        $response = new Response(300, null);
        $this->assertFalse($response->wasSuccessful());
    }

    /**
     * @testWith ["abc123", "https://rollbar.com/occurrence/uuid/?uuid=abc123"]
     *           ["a bar", "https://rollbar.com/occurrence/uuid/?uuid=a+bar"]
     */
    public function testUrl(string $uuid, string $expectedOccurrenceUrl): void
    {
        $response = new Response(200, "fake", $uuid);
        $this->assertEquals($expectedOccurrenceUrl, $response->getOccurrenceUrl());
    }
}
