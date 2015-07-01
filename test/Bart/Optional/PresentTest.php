<?php
namespace Bart\Optional;

use Bart\BaseTestCase;

class PresentTest extends BaseTestCase
{
    private $value = 'box_rox';

    private function getPresent()
    {
        return new Present($this->value);
    }

    public function testConstruct()
    {
        new Present($this->value);

        $this->setExpectedException('Bart\Exceptions\IllegalStateException', 'Disallowed null in reference.');
        new Present(null);
    }

    public function testConstructFromAbsent()
    {
        $this->setExpectedException('Bart\Exceptions\IllegalStateException', 'Disallowed null in reference.');
        new Present(Optional::absent());
    }

    public function testConstructFromPresent()
    {
        $present = $this->getPresent();
        $newPresent = new Present($present);

        $this->assertInstanceOf('Bart\Optional\Present', $newPresent);
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

        $this->assertInstanceOf('Bart\Optional\Present', $mapped);
        $this->assertEquals(strtoupper($this->value), $mapped->get());
    }

    /**
     * Tests that the map function will return an instance of Absent
     * if the callable passed returns null
     */
    public function testMapReturnsAbsentOnNull()
    {
        $present = $this->getPresent();
        $mapped = $present->map(function($string) {
            return null;
        });

        $this->assertInstanceOf('Bart\Optional\Absent', $mapped);
    }

    /**
     * Tests that the map function will return an instance of Absent
     * if the callable passed returns Absent
     */
    public function testMapReturnsAbsentOnAbsent()
    {
        $present = $this->getPresent();
        $mapped = $present->map(function($string) {
            return Optional::absent();
        });

        $this->assertInstanceOf('Bart\Optional\Absent', $mapped);
    }

    public function testEquals()
    {
        $present = $this->getPresent();

        $this->assertTrue($present->equals(new Present($this->value)));
        $this->assertFalse($present->equals(new Present('some other value')));
        $this->assertFalse($present->equals(Absent::instance()));
    }

}
