<?php
namespace Bart\Git;

use Bart\BaseTestCase;

class PersonTest extends BaseTestCase
{
    public function testBaseCase()
    {
        $fakeName = 'Fake Name';
        $fakeEmail = 'email@example.com';
        $person = new Person($fakeName, $fakeEmail);
        $this->assertSame($fakeName, $person->getName());
        $this->assertSame($fakeEmail, $person->getEmail());
    }

    public function testInvalidEmailThrowsException()
    {
        $fakeName = 'Fake Name';
        $fakeEmailInvalid = 'I am an invalid email';
        $this->setExpectedException('\InvalidArgumentException');
        new Person($fakeName, $fakeEmailInvalid);
    }

}
