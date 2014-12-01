<?php
namespace Bart\Configuration;

use Bart\BaseTestCase;

class ProjectConfigurationTest extends BaseTestCase
{
	public function testConfigsAreLoadedFromGit()
	{
		$head = $this->shmock('\Bart\Git\Commit', function($head) {
			$head->disable_original_constructor();
			$iniContents = <<<INI
[favorites]
color = green
food = turkey
number = 42

INI;
			// This expected path should match based on name of class
			$head->rawFileContents('etc/testproject.conf')->return_value($iniContents);
		});

		$configs = new TestProjectConfig($head);
		$this->assertEquals('green', $configs->color(), 'color config value');
	}
}

/**
 * Class TestProjectConfig to facilitate testing of ProjectConfiguration
 * @package Bart\Configuration
 */
class TestProjectConfig extends ProjectConfiguration
{
	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public function README()
	{
		return 'Test configuration class';
	}

	public function color()
	{
		return $this->getValue('favorites', 'color');
	}

	public function food()
	{
		return $this->getValue('favorites', 'food');
	}

	public function number()
	{
		return $this->getNumeric('favorites', 'number');
	}
}