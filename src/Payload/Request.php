<?php namespace Rollbar\Payload;

class Request implements \Serializable
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
    private $utilities;

    public function __construct()
    {
        $this->utilities = new \Rollbar\Utilities();
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
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
        $this->body = $body;
        return $this;
    }

    public function getUserIp()
    {
        return $this->userIp;
    }

    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;
        return $this;
    }

    public function getExtras()
    {
        return $this->extra;
    }

    public function setExtras($extras)
    {
        $this->extra = $extras;
    }

    public function setSession($session)
    {
        $this->extra['session'] = $session;
    }

    public function serialize()
    {
        $result = array(
            "url" => $this->url,
            "method" => $this->method,
            "headers" => $this->headers,
            "params" => $this->params,
            "GET" => $this->get,
            "query_string" => $this->queryString,
            "POST" => $this->post,
            "body" => $this->body,
            "user_ip" => $this->userIp,
        );
        foreach ($this->extra as $key => $val) {
            $result[$key] = $val;
        }
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities->serializeForRollbar($result, array_keys($this->extra), $objectHashes);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
