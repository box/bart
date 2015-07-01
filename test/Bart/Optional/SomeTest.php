<?php
namespace Bart\Optional;

use Bart\BaseTestCase;

class SomeTest extends BaseTestCase
{
    private $value = 'box_rox';

    private function getPresent()
    {
        return new Some($this->value);
    }

    public function testConstruct()
    {
        new Some($this->value);

        $this->setExpectedException('Bart\Exceptions\IllegalStateException', 'Disallowed null in reference.');
        new Some(null);
    }

    public function testConstructFromAbsent()
    {
        $this->setExpectedException('Bart\Exceptions\IllegalStateException', 'Disallowed null in reference.');
        new Some(Optional::absent());
    }

    public function testConstructFromPresent()
    {
        $present = $this->getPresent();
        $newPresent = new Some($present);

        $this->assertInstanceOf('Bart\Optional\Some', $newPresent);
        $this->assertEquals($this->value, $newPresent->get());
    }

    public function testIsPresent()
    {
        $present = $this->getPresent();
        $this->assertTrue($present->isPresent());

        $this->assertFalse($present->isAbsent());
    }

    public function testGet()
    {
        $present = $this->getPresent();
        $this->assertEquals($this->value, $present->get());
    }

    public function testGetOrElse()
    {
        $present = $this->getPresent();
        $this->assertEquals($this->value, $present->getOrElse('boxbox'));
    }

    public function testGetOrNull()
    {
        $present = $this->getPresent();
        $this->assertEquals($this->value, $present->getOrNull());
    }

    public function testMap()
    {
        $present = $this->getPresent();
        $mapped = $present->map('strtoupper');

        $this->assertInstanceOf('Bart\Optional\Some', $mapped);
        $this->assertEquals(strtoupper($this->value), $mapped->get());
    }

    /**
     * Tests that the map function will return an instance of None
     * if the callable passed returns null
     */
    public function testMapReturnsAbsentOnNull()
    {
        $present = $this->getPresent();
        $mapped = $present->map(function($string) {
            return null;
        });

        $this->assertInstanceOf('Bart\Optional\None', $mapped);
    }

    /**
     * Tests that the map function will return an instance of None
     * if the callable passed returns None
     */
    public function testMapReturnsAbsentOnAbsent()
    {
        $present = $this->getPresent();
        $mapped = $present->map(function($string) {
            return Optional::absent();
        });

        $this->assertInstanceOf('Bart\Optional\None', $mapped);
    }

    public function testEquals()
    {
        $present = $this->getPresent();

        $this->assertTrue($present->equals(new Some($this->value)));
        $this->assertFalse($present->equals(new Some('some other value')));
        $this->assertFalse($present->equals(None::instance()));
    }

}
