<?php
namespace Bart\Configuration;

/**
 * Include this trait to add the base tests for configuration classes
 * Needs to be mixed in with a PHPUnit_Base_TestCase
 */
trait ProjectConfigurationBaseTests
{
	/**
	 * Complement to ConfigurationBaseTests::assertREADME() for ProjectConfiguration classes
	 * See description there
	 */
	public function testReadme()
	{
		$configFileName = $this->configFileName();
		$configurationClassName = TestConfigurationsHelper::deduceConfigClassName(__CLASS__);

		$logger = \Logger::getLogger(__CLASS__);
		$logger->debug("Starting README test for $configurationClassName");
		$readme = TestConfigurationsHelper::getReadme($this, $configurationClassName);

		$logger->debug("Stubbing contents of $configFileName");
		$commitDbl = $this->shmock('\Bart\Git\Commit', function($commitDbl) use ($configFileName, $readme) {
			$commitDbl->disable_original_constructor();
			$commitDbl->rawFileContents("etc/{$configFileName}")->return_value($readme);
		});

		$configs = new $configurationClassName($commitDbl);

		TestConfigurationsHelper::assertConfigurationGetters($configs, $this, $configurationClassName);
	}
}