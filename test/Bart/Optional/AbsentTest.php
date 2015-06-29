<?php
namespace Bart\Optional;

use Bart\BaseTestCase;

class AbsentTest extends BaseTestCase
{
    public function testGetInstance()
    {
        $absent = Absent::instance();
        $this->assertInstanceOf('Bart\Optional\Absent', $absent);
    }

    public function testIsPresent()
    {
        $absent = Absent::instance();
        $this->assertFalse($absent->isPresent());
    }

    public function testIsAbsent()
    {
        $absent = Absent::instance();
        $this->assertTrue($absent->isAbsent());
    }

    public function testGet()
    {
        $absent = Absent::instance();
        $this->setExpectedException('Bart\Exceptions\IllegalStateException', 'Trying to get a nonexistent value.');
        $absent->get();
    }

    public function testGetOrElse()
    {
        $absent = Absent::instance();
        $this->assertEquals('foo', $absent->getOrElse('foo'));
    }

    public function testGetOrNull()
    {
        $absent = Absent::instance();
        $this->assertSame(null, $absent->getOrNull());
    }

    public function testMap()
    {
        $absent = Absent::instance();
        $this->assertInstanceOf('Bart\Optional\Absent', $absent->map('printf'));
    }

    public function testEquals()
    {
        $absent = Absent::instance();
        $this->assertFalse($absent->equals(new Present('foo')));
        $this->assertTrue($absent->equals($absent));
    }

}