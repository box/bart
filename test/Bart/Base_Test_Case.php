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
	 * Assert that $anonFunc fails with exception of $type and $msg
	 */
	protected function assertThrows($type, $msg, $anonFunc)
	{
		try
		{
			$anonFunc();
			$this->fail('Expected test to fail, but it succeeded. '
					. "Expected - exception $type; with msg: $msg");
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
