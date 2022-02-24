<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class Request implements SerializerInterface
{
    use UtilitiesTrait;

    private ?string $url = null;
    private ?string $method = null;
    private ?array $headers = null;
    private ?array $params = null;
    private ?array $get = null;
    private ?string $queryString = null;
    private ?array $post = null;
    private ?string $body = null;
    private ?string $userIp = null;
    private array $extra = array();

    public function __construct()
    {
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function setHeaders(?array $headers = null): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params = null): self
    {
        $this->params = $params;
        return $this;
    }

    public function getGet(): ?array
    {
        return $this->get;
    }

    public function setGet(?array $get = null): self
    {
        $this->get = $get;
        return $this;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function setQueryString(?string $queryString): self
    {
        $this->queryString = $queryString;
        return $this;
    }

    public function getPost(): ?array
    {
        return $this->post;
    }

    public function setPost(?array $post = null): self
    {
        $this->post = $post;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    public function setUserIp(?string $userIp): self
    {
        $this->userIp = $userIp;
        return $this;
    }

    public function getExtras(): array
    {
        return $this->extra;
    }

    public function setExtras(array $extras): self
    {
        $this->extra = $extras;
        return $this;
    }

    public function setSession(array $session): self
    {
        $this->extra['session'] = $session;
        return $this;
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
        
        return $this->utilities()->serializeForRollbarInternal($result, array_keys($this->extra));
    }
}
