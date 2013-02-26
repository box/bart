<?php
namespace Bart;

class BaseTestCaseTest extends \Bart\BaseTestCase
{
	public function testCaptureOutputBuffer()
	{
		$output = $this->captureOutputBuffer(function($phpu)
		{
			$phpu->assertTrue(true, 'using phpunit');
			echo 'Catch me!';
		});

		$this->assertEquals('Catch me!', $output, 'buffer output');
	}

	public function testAssertThrowsWhenCallableSucceeds()
	{
		$failed = false;
		try
		{
			$this->assertThrows('\Exception', 'My random message', function() {});
		}
		catch (\PHPUnit_Framework_AssertionFailedError $e)
		{
			$failed = true;
		}

		$this->assertTrue($failed, 'Expected assertThrows() to fail() when given a successful callable');
	}

	public function testAssertThrowsAssertsTypeAndMessage()
	{
		Autoloader::autoload('Git');
		$this->assertThrows('\Bart\Git_Exception', 'Contrived message', function()
		{
			throw new Git_Exception('This is a Contrived message');
		});
	}
}
