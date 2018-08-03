<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Context;

class ContextTest extends BaseRollbarTest
{
    public function testContextPre()
    {
        $pre = array("hello", "world");
        $context = new Context($pre, array());
        $this->assertEquals($pre, $context->getPre());

        $pre2 = array("lineone", "linetwo");
        $this->assertEquals($pre2, $context->setPre($pre2)->getPre());
    }

    public function testContextPost()
    {
        $post = array("four", "five");
        $context = new Context(array(), $post);
        $this->assertEquals($post, $context->getPost());

        $post2 = array("six", "seven", "eight");
        $this->assertEquals($post2, $context->setPost($post2)->getPost());
    }

    public function testEncode()
    {
        $context = new Context(array(), array());
        $encoded = json_encode($context->serialize());
        $this->assertEquals('{"pre":[],"post":[]}', $encoded);

        $context = new Context(array("one"), array("three"));
        $encoded = json_encode($context->serialize());
        $this->assertEquals('{"pre":["one"],"post":["three"]}', $encoded);
    }
}
