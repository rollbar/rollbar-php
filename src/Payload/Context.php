<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class Context implements SerializerInterface
{
    use UtilitiesTrait;

    public function __construct(private ?array $pre, private ?array $post)
    {
    }

    public function getPre(): ?array
    {
        return $this->pre;
    }

    public function setPre(array $pre): self
    {
        $this->pre = $pre;
        return $this;
    }

    public function getPost(): ?array
    {
        return $this->post;
    }

    public function setPost(array $post): self
    {
        $this->post = $post;
        return $this;
    }

    public function serialize()
    {
        $result = array(
            "pre" => $this->pre,
            "post" => $this->post,
        );
        
        return $this->utilities()->serializeForRollbarInternal($result);
    }
}
