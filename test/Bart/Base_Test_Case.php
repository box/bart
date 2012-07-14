<?php
namespace Bart;

class Base_Test_Case extends \PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
		Diesel::disableDefault();
	}

	/**
	 * Called automatically by PHP before tests are run
	 */
	public function setUp()
	{
		Diesel::reset();
	}

	protected function assertArrayKeyNotExists($key, array $array, $message = '')
	{
		$this->assertFalse(array_key_exists($key, $array), $message);
	}

	/**
	 * Assert that @param closure fails with exception $type and $msg
	 */
	protected function assert_throws($type, $msg, $closure)
	{
		try
		{
			$closure();
			$this->fail("Expected exception, but succeeded. "
					. "Expected - type: $type; msg: $msg");
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf($type, $e,
				'Expected thrown exception of type ' . $type);
			$this->assertEquals($msg, $e->getMessage(),
				'Expected thrown exception to have msg ' . $msg);
		}
	}
}
