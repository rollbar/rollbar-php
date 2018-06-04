<?php namespace Rollbar\Payload;

class Context implements \Serializable
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

    public function serialize()
    {
        $result = array(
            "pre" => $this->pre,
            "post" => $this->post,
        );
        return $this->utilities->serializeForRollbar($result);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
