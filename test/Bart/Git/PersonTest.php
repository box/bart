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
}
