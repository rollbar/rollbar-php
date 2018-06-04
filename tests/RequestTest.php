<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Request;

class RequestTest extends BaseRollbarTest
{
    public function testUrl()
    {
        $url = "www.rollbar.com";
        $request = new Request();
        $request->setUrl($url);
        $this->assertEquals($url, $request->getUrl());

        $url2 = "www.google.com";
        $this->assertEquals($url2, $request->setUrl($url2)->getUrl());
    }

    public function testMethod()
    {
        $method = "POST";
        $request = new Request();
        $request->setMethod($method);
        $this->assertEquals($method, $request->getMethod());

        $method2 = "GET";
        $this->assertEquals($method2, $request->setMethod($method2)->getMethod());
    }

    public function testHeaders()
    {
        $headers = array("Auth-X" => "abc352", "Hello" => "World");
        $request = new Request();
        $request->setHeaders($headers);
        $this->assertEquals($headers, $request->getHeaders());

        $headers2 = array("Goodbye" => "And thanks for all the fish");
        $this->assertEquals($headers2, $request->setHeaders($headers2)->getHeaders());
    }

    public function testParams()
    {
        $params = array(
            "controller" => "project",
            "action" => "index"
        );
        $request = new Request();
        $request->setParams($params);
        $this->assertEquals($params, $request->getParams());

        $params2 = array("War" => "and Peace");
        $this->assertEquals($params2, $request->setParams($params2)->getParams());
    }

    public function testGet()
    {
        $get = array("query" => "where's waldo?", "page" => 15);
        $request = new Request();
        $request->setGet($get);
        $this->assertEquals($get, $request->getGet());

        $get2 = array("skip" => "4", "bucket_size" => "25");
        $this->assertEquals($get2, $request->setGet($get2)->getGet());
    }

    public function testQueryString()
    {
        $queryString = "?slug=Rollbar&update=true";
        $request = new Request();
        $request->setQueryString($queryString);
        $this->assertEquals($queryString, $request->getQueryString());

        $queryString2 = "?search=Hello%2a";
        $actual = $request->setQueryString($queryString2)->getQueryString();
        $this->assertEquals($queryString2, $actual);
    }

    public function testPost()
    {
        $post = array("Big" => "Data");
        $request = new Request();
        $request->setPost($post);
        $this->assertEquals($post, $request->getPost());

        $post2 = array(
            "data" => array(
                "Data" => "Parameters"
            ),
            "access_token" => $this->getTestAccessToken()
        );
        $this->assertEquals($post2, $request->setPost($post2)->getPost());
    }

    public function testBody()
    {
        $body = "a long string\nwith new lines and stuff";
        $request = new Request();
        $request->setBody($body);
        $this->assertEquals($body, $request->getBody());

        $body2 = "In the city of York there existed a society of magicians...";
        $this->assertEquals($body2, $request->setBody($body2)->getBody());
    }

    public function testUserIp()
    {
        $userIp = "192.0.1.12";
        $request = new Request();
        $request->setUserIp($userIp);
        $this->assertEquals($userIp, $request->getUserIp());

        $userIp2 = "172.68.205.3";
        $this->assertEquals($userIp2, $request->setUserIp($userIp2)->getUserIp());
    }

    public function testExtra()
    {
        $request = new Request();
        $request->setExtras(array("test" => "testing"));
        $extras = $request->getExtras();
        $this->assertEquals("testing", $extras["test"]);
    }

    public function testEncode()
    {
        $request = new Request();
        $request->setUrl("www.rollbar.com/account/project")
            ->setMethod("GET")
            ->setHeaders(array(
                "CSRF-TOKEN" => "42",
                "X-SPEED" => "THEFLASH"
            ))
            ->setParams(array(
                "controller" => "ProjectController",
                "method" => "index"
            ))
            ->setGet(array(
                "fetch_account" => "true",
                "error_level" => "11"
            ))
            ->setQueryString("?fetch_account=true&error_level=11")
            ->setUserIp("170.16.58.0");

        $request->setExtras(array("test" => "testing"));

        $expected = '{' .
            '"url":"www.rollbar.com\\/account\\/project",' .
            '"method":"GET",' .
            '"headers":{' .
                '"CSRF-TOKEN":"42","X-SPEED":"THEFLASH"' .
            '},' .
            '"params":{' .
                 '"controller":"ProjectController",' .
                 '"method":"index"' .
             '},' .
             '"GET":{' .
                 '"fetch_account":"true",' .
                 '"error_level":"11"' .
             '},' .
             '"query_string":"?fetch_account=true&error_level=11",' .
             '"user_ip":"170.16.58.0",' .
             '"test":"testing"' .
        '}';

        $this->assertEquals($expected, json_encode($request->serialize()));
    }
}
