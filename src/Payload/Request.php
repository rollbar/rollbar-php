<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Request implements \JsonSerializable
{
    private $url;
    private $method;
    private $headers;
    private $params;
    private $get;
    private $queryString;
    private $post;
    private $body;
    private $userIp;
    private $extra = array();

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        Utilities::validateString($url, "url");
        $this->url = $url;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        Utilities::validateString($method, "method");
        $this->method = $method;
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders(array $headers = null)
    {
        $this->headers = $headers;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams(array $params = null)
    {
        $this->params = $params;
        return $this;
    }

    public function getGet()
    {
        return $this->get;
    }

    public function setGet(array $get = null)
    {
        $this->get = $get;
        return $this;
    }

    public function getQueryString()
    {
        return $this->queryString;
    }

    public function setQueryString($queryString)
    {
        Utilities::validateString($queryString, "queryString");
        $this->queryString = $queryString;
        return $this;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost(array $post = null)
    {
        $this->post = $post;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        Utilities::validateString($body, "body");
        $this->body = $body;
        return $this;
    }

    public function getUserIp()
    {
        return $this->userIp;
    }

    public function setUserIp($userIp)
    {
        Utilities::validateString($userIp, "userIp");
        $this->userIp = $userIp;
        return $this;
    }

    public function __get($key)
    {
        return isset($this->extra[$key]) ? $this->extra[$key] : null;
    }

    public function __set($key, $val)
    {
        $this->extra[$key] = $val;
    }

    public function jsonSerialize()
    {
        $result = get_object_vars($this);
        unset($result['extra']);
        foreach ($this->extra as $key => $val) {
            $result[$key] = $val;
        }
        $overrideNames = array(
            "get" => "GET",
            "post" => "POST"
        );
        return Utilities::serializeForRollbar($result, $overrideNames, array_keys($this->extra));
    }
}
