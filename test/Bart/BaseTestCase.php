<?php
namespace Bart;

class BaseTestCase extends \PHPUnit_Framework_TestCase
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

	/**
	 * Register the $stub with Diesel for requests for $class_name.
	 * @note this is mainly useful for parameter-less constructors
	 * @param string $class_name
	 * @param mixed $stub
	 */
	public function registerDiesel($class_name, $stub)
	{
		Diesel::registerInstantiator($class_name, function() use ($stub)
		{
			return $stub;
		});
	}


	protected function assertArrayKeyNotExists($key, array $array, $message = '')
	{
		$this->assertFalse(array_key_exists($key, $array), $message);
	}

	/**
	 * Provide a temporary file path to use for tests and always make sure it gets removed
	 * @param callable $func (TestCase, String) => () Will do the stuff to the temporary file
	 */
	protected function doStuffToTempFile($func)
	{
		$filename = BART_DIR . 'phpunit-random-file-please-delete.txt';
		@unlink($filename);

		try
		{
			$func($this, $filename);
		}
		catch (\Exception $e)
		{
			@unlink($filename);
			throw $e;
		}

		@unlink($filename);
	}

	/**
	 * Assert that $anonFunc fails with exception of $type and $msg
	 * @param string $type Exception type
	 * @param string $msg Exception message
	 * @param callable $anonFunc (PHPUnit) => () Anonymous function containing code expected to fail
	 */
	protected function assertThrows($type, $msg, $anonFunc)
	{
		try
		{
			$anonFunc($this);
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
