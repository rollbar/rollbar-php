<?php namespace Rollbar;

use Rollbar\Response;

class ResponseTest extends BaseRollbarTest
{
    public function testStatus()
    {
        $r = new Response(200, array("whatever"=>5));
        $this->assertEquals(200, $r->getStatus());
    }

    public function testInfo()
    {
        $r = new Response(200, "FAKE INFO");
        $this->assertEquals("FAKE INFO", $r->getInfo());
    }

    public function testUuid()
    {
        $r = new Response(200, "FAKE INFO", "abc123");
        $this->assertEquals("abc123", $r->getUuid());
    }

    public function testWasSuccessful()
    {
        $response = new Response(200, null);
        $this->assertTrue($response->wasSuccessful());
        $response = new Response(199, null);
        $this->assertFalse($response->wasSuccessful());
        $response = new Response(300, null);
        $this->assertFalse($response->wasSuccessful());
    }

    public function testUrl()
    {
        $expected = "https://rollbar.com/occurrence/uuid/?uuid=abc123";
        $response = new Response(200, "fake", "abc123");
        $this->assertEquals($expected, $response->getOccurrenceUrl());
    }
}
