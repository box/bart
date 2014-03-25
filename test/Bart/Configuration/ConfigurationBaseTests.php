<?php
namespace Bart\Configuration;
use Bart\BaseTestCase;

/**
 * Include this trait to add the base tests for configuration classes
 * Needs to be mixed in with a PHPUnit_Base_TestCase
 */
trait ConfigurationBaseTests
{
	public function testReadme()
	{
		$confName = $this->configFileName();
		$name = __CLASS__;

		$pos = strrpos($name, '_Test');
		if ($pos === false) {
			$pos = strrpos($name, 'Test');
		}

		$name = substr($name, 0, $pos);
		ConfigurationTest::assertREADME($this, $name, $confName);
	}
}
