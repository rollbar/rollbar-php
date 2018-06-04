<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Person;

class PersonTest extends BaseRollbarTest
{
    public function testId()
    {
        $id = "rollbar-php";
        $person = new Person($id);
        $this->assertEquals($id, $person->getId());

        $id2 = "RollbarPHP";
        $this->assertEquals($id2, $person->setId($id2)->getId());
    }

    public function testUsername()
    {
        $username = "user@rollbar.com";
        $person = new Person("15", $username);
        $this->assertEquals($username, $person->getUsername());

        $username2 = "user-492";
        $this->assertEquals($username2, $person->setUsername($username2)->getUsername());
    }

    public function testEmail()
    {
        $email = "1.0.0";
        $person = new Person("Rollbar_Master", null, $email);
        $this->assertEquals($email, $person->getEmail());

        $email2 = "1.0.1";
        $this->assertEquals($email2, $person->setEmail($email2)->getEmail());
    }

    public function testExtra()
    {
        $person = new Person("42");
        $person->test = "testing";
        $this->assertEquals("testing", $person->test);
    }

    public function testEncode()
    {
        $person = new Person("1024");
        $person->setUsername("username")
            ->setEmail("user@gmail.com");
        $person->Settings = array(
            "send_email" => true
        );
        $encoded = json_encode($person->serialize());
        $expected ='{"id":"1024","username":"username","email":"user@gmail.com","Settings":{"send_email":true}}';
        $this->assertEquals($expected, $encoded);
    }
}
