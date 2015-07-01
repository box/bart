<?php
namespace Bart\Optional;

use Bart\BaseTestCase;

class NoneTest extends BaseTestCase
{
    public function testGetInstance()
    {
        $absent = None::instance();
        $this->assertInstanceOf('Bart\Optional\None', $absent);
    }

    public function testIsPresent()
    {
        $absent = None::instance();
        $this->assertFalse($absent->isPresent());
    }

    public function testIsAbsent()
    {
        $absent = None::instance();
        $this->assertTrue($absent->isAbsent());
    }

    public function testGet()
    {
        $absent = None::instance();
        $this->setExpectedException('Bart\Exceptions\IllegalStateException', 'Trying to get a nonexistent value.');
        $absent->get();
    }

    public function testGetOrElse()
    {
        $absent = None::instance();
        $this->assertEquals('foo', $absent->getOrElse('foo'));
    }

    public function testGetOrNull()
    {
        $absent = None::instance();
        $this->assertSame(null, $absent->getOrNull());
    }

    public function testMap()
    {
        $absent = None::instance();
        $this->assertInstanceOf('Bart\Optional\None', $absent->map('printf'));
    }

    public function testEquals()
    {
        $absent = None::instance();
        $this->assertFalse($absent->equals(new Some('foo')));
        $this->assertTrue($absent->equals($absent));
    }

}
