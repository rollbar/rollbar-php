<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Context implements \JsonSerializable
{
    private $pre;
    private $post;

    public function __construct($pre, $post)
    {
        $this->setPre($pre);
        $this->setPost($post);
    }

    public function getPre()
    {
        return $this->pre;
    }

    public function setPre($pre)
    {
        $this->validateAllString($pre, "pre");
        $this->pre = $pre;
        return $this;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost($post)
    {
        $this->validateAllString($post, "post");
        $this->post = $post;
        return $this;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }

    private function validateAllString($arr, $arg)
    {
        foreach ($arr as $line) {
            if (!is_string($line)) {
                throw new \InvalidArgumentException("\$$arg must be all strings");
            }
        }
    }
}
