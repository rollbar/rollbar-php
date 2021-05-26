<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\UtilitiesTrait;

class Context implements \Serializable
{
    use UtilitiesTrait;

    public function __construct(private $pre, private $post)
    {
    }

    public function getPre()
    {
        return $this->pre;
    }

    public function setPre($pre)
    {
        $this->pre = $pre;
        return $this;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost($post)
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
    
    public function unserialize(string $serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
