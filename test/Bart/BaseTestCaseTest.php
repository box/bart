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
}

