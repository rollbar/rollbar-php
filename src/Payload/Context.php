<?php namespace Rollbar\Payload;

class Context implements \JsonSerializable
{
    private $pre;
    private $post;
    private $utilities;

    public function __construct($pre, $post)
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setPre($pre);
        $this->setPost($post);
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

    public function jsonSerialize()
    {
        $result = array(
            "pre" => $this->pre,
            "post" => $this->post,
        );
        return $this->utilities->serializeForRollbar($result);
    }
}
