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
	 * Assert that $anonFunc throws $e, where $e: $type and $e.message contains $msg
	 * @param string $type Exception type
	 * @param string $msgNeedle Text expected to occur within exception message.
	 *                  Use empty string to ignore.
	 * @param callable $anonFunc (PHPUnit) => () Anonymous function containing code expected to fail
	 */
	protected function assertThrows($type, $msgNeedle, $anonFunc)
	{
		try
		{
			$anonFunc($this);
			$this->fail('Expected test to fail, but it succeeded. '
				. "Expected: exception = $type, message ~ $msgNeedle");
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf($type, $e, 'Expected type of exception message');
			$this->assertContains($msgNeedle, $e->getMessage(), 'Expected text in exception message');
		}
	}

	/**  
	 * Capture the output from the output buffer
	 * E.g. echo output
	 * @note Use intelligently
	 * @param $closure {anonymous functions} An anonymous function that presumably produces output.
	 * @returns The output
	 */
	protected function captureOutputBuffer($closure)
	{    
		ob_start();
		try  
		{    
			$closure();
			$output = ob_get_contents();
			ob_end_clean();
		}    
		catch (Exception $e)
		{    
			ob_end_clean();
			throw $e;
		}    

		return $output;
	}  
}
