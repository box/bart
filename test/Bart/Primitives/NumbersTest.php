<?php
namespace Bart\Primitives;

use Bart\BaseTestCase;
use Bart\Log4PHP;

class NumbersTest extends BaseTestCase
{
	public function test_cast_to_numeric_with_digits()
	{
		$this->assertEquals(42.1, Numbers::castToNumeric('42.1'));
	}

	public function test_cast_to_numeric_with_padded_text()
	{
		$this->assertEquals(42.1, Numbers::castToNumeric(' 42.1 '));
	}

	public function test_cast_to_numeric_with_two_numerics()
	{
		$this->assertThrows('\Bart\Primitives\PrimitivesException', 'not coerce', function() {
			Numbers::castToNumeric('42.1 2');
		});
	}

	public function test_cast_to_numeric_with_non_numerics()
	{
		$this->assertThrows('\Bart\Primitives\PrimitivesException', 'not coerce', function() {
			Numbers::castToNumeric('42.1a');
		});
	}

	public function test_cast_to_numeric_when_empty()
	{
		$this->assertThrows('\Bart\Primitives\PrimitivesException', 'not coerce', function() {
			Numbers::castToNumeric('');
		});
	}

	public function test_cast_to_numeric_when_already_float()
	{
		$this->assertEquals(42.1, Numbers::castToNumeric(42.1));
	}

	public function test_cast_to_numeric_when_already_int()
	{
		$this->assertEquals(42, Numbers::castToNumeric(42));
	}
}
