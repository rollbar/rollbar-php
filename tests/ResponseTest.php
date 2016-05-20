<?php namespace Rollbar;

use Rollbar\Response;

class ResponseTest
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
        $this->assertTrue((new Response(200, null))->wasSuccessful());
        $this->assertFalse((new Response(199, null))->wasSuccessful());
        $this->assertFalse((new Response(300, null))->wasSuccessful());
    }

    public function testUrl()
    {
        $expected = "https://rollbar.com/occurrence/uuid/?uuid=abc123";
        $this->assertEquals($expected, (new Response(200, "fake", "abc123"))->getOccurrenceUrl());
    }
}
