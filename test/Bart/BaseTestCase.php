<?php
namespace Bart;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
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
	 * Register the $stub with Diesel for requests for $className.
	 * @note this is mainly useful for parameter-less constructors
	 * @param string $className Name of class being registered
	 * @param mixed $stub This will be returned by @see Diesel
	 */
	public function registerDiesel($className, $stub)
	{
		Diesel::registerInstantiator($className, function() use ($stub)
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
	 * Provide a temporary directory path to use for tests and always make sure it gets removed
	 * @param callable $func (TestCase, String) => () Will do the stuff to the temporary directory
	 */
	protected function doStuffWithTempDir($func)
	{
		$shell = new Shell();
		$dir = $shell->mktempdir();

		try
		{
			$func($this, $dir);
		}
		catch (\Exception $e)
		{
			@unlink($dir);
			throw $e;
		}

		@unlink($dir);
	}

	/**
	 * Assert that $anonFunc throws $e, where $e: $type and $e.message contains $msg
	 * @param string $type Exception type
	 * @param string $msgNeedle Text expected to occur within exception message.
	 *                  Use empty string to ignore.
	 * @param callable $func (PHPUnit) => () Anonymous function containing code expected to fail
	 */
	protected function assertThrows($type, $msgNeedle, $func)
	{
		try
		{
			$func($this);
			$this->fail('Expected test to fail, but it succeeded. '
				. "Expected: exception = $type, message ~ $msgNeedle");
		}
		catch (\Exception $e)
		{
			// First make sure the caught exception is not from fail()
			$this->assertNotInstanceOf('\PHPUnit_Framework_AssertionFailedError', $e, $e->getMessage());

			$this->assertInstanceOf($type, $e, 'Expected type of exception message');
			$this->assertContains($msgNeedle, $e->getMessage(), 'Expected text in exception message');
		}
	}

	/**  
	 * Capture the output from the output buffer
	 *
	 * @note Use intelligently
	 * @param callable $func [(PHPUnit_Framework_TestCase) => string] Anonymous function that presumably produces output
	 * @return string The output of the closure
	 */
	protected function captureOutputBuffer($func)
	{    
		ob_start();
		try  
		{    
			$func($this);
			$output = ob_get_contents();
			ob_end_clean();
		}    
		catch (\Exception $e)
		{    
			ob_end_clean();
			throw $e;
		}    

		return $output;
	}  
}
