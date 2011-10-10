<?php
class Bart_Base_Test_Case extends PHPUnit_Framework_TestCase
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
}


