<?php
namespace Bart\Configuration;
use Bart\BaseTestCase;

/**
 * Include this trait to add the base tests for configuration classes
 * Needs to be mixed in with a PHPUnit_Base_TestCase
 */
trait ConfigurationBaseTests
{
	/**
	 * Asserts that the sample conf returned by README() on mixed in SUT class
	 * can be parsed successfully by the SUT itself
	 *
	 * This is a good practice to ensure that README's stay up to date with their class
	 *
	 * Technical details:
	 *    Will parse and use README() as the configuration
	 *    and call public methods on the class. Any missing or misconfigured
	 *    samples in the README will raise exceptions.
	 */
	public function testReadme()
	{
		$configurationClassName = TestConfigurationsHelper::deduceConfigClassName(__CLASS__);
		$configFileName = $this->configFileName();

		// Stub any calls to STDIN
		$this->shmockAndDieselify('\Bart\Shell', function($shell) {
			$shell->std_in_secret()->any()->return_value('secret');
			$shell->std_in()->any()->return_value('secret');
		});

		// Write the README INI into a file and then load that
		$this->doStuffWithTempDir(function (BaseTestCase $phpu, $dirName)
		use ($configurationClassName, $configFileName) {
			$logger = \Logger::getLogger(__CLASS__);
			$logger->debug("Starting README test for $configurationClassName");

			$readme = TestConfigurationsHelper::getReadme($phpu, $configurationClassName);
			file_put_contents("{$dirName}/$configFileName", $readme);

			// Reset the cache so that the configs will be read from disk
			$logger->debug("Configuring system to load configs from $dirName");
			TestConfigurationsHelper::reset($dirName);
			$configs = new $configurationClassName();

			TestConfigurationsHelper::assertConfigurationGetters($configs, $phpu, $configurationClassName);
		});
	}
}
