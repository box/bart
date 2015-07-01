<?php
namespace Bart\Optional;

use Bart\BaseTestCase;

class OptionalTest extends BaseTestCase
{
    public function testAbsent()
    {
        $absent = Optional::absent();
        $this->assertInstanceOf('Bart\Optional\None', $absent);
    }

    public function testFrom()
    {
        $value = 'box_rox';
        $present = Optional::from($value);

        $this->assertInstanceOf('Bart\Optional\Some', $present);
        $this->assertEquals($value, $present->get());

        $this->setExpectedException('Bart\Exceptions\IllegalStateException');
        Optional::from(null);
    }

    public function testFromNullable()
    {
        $value = 'box_rox';
        $present = Optional::fromNullable($value);

        $this->assertInstanceOf('Bart\Optional\Some', $present);
        $this->assertEquals($value, $present->get());

        $absent = Optional::fromNullable(null);
        $this->assertInstanceOf('Bart\Optional\None', $absent);
    }

}
