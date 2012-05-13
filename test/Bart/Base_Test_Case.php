<?php
namespace Bart;

class Base_Test_Case extends \PHPUnit_Framework_TestCase
{
	/**
	 * Called automatically by PHP before tests are run
	 */
	protected function setUp()
	{
		// Invoke any set up defined on child test
		// Allows us to ensure __this__.setUp is always run regardless of child
		if (method_exists($this, 'set_up')) $this->set_up();
	}

	/**
	 * Called automatically by PHP after tests are run
	 */
	protected function tearDown()
	{
		// Invoke any tear down defined on child test
		// Allows us to ensure __this__.tearDown is always run regardless of child
		if (method_exists($this, 'tear_down')) $this->tear_down();
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
