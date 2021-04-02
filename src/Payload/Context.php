<?php namespace Rollbar\Payload;

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
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities()->serializeForRollbar($result, null, $objectHashes);
    }
    
    public function unserialize(string $serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
