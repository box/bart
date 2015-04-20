<?php
namespace Bart\Configuration;

use Bart\BaseTestCase;
use Bart\Diesel;
use Bart\Shell;
use Bart\Util\Reflection_Helper;

class ConfigurationTest extends BaseTestCase
{
	private static $sampleConfigs = array(
		// Coincidentally match values in test/etc/conf-parser.conf
		'favorites' => array(
			'color' => 'Salamander',
			'drink' => 'Moscow Mule',
			'number' => '42',
		),
	);

	public function setUp()
	{
		TestConfigurationsHelper::reset();
		parent::setUp();
	}

	public function testSetConfigurationTwiceFails()
	{
		$this->assertThrows('Bart\Configuration\ConfigurationException', 'Already set to path 1', function () {
			Configuration::configure('path 1');
			Configuration::configure('path 2');
		});
	}

	public function testLoadThrowsWhenNotConfigured()
	{
		$this->assertThrows('Bart\Configuration\ConfigurationException', 'path not set!', function () {
			new TestConfig(false);
		});
	}

	public function testCanLoadFromFile()
	{
		Diesel::registerInstantiator('\Bart\Shell', function () {
			// Actually want to see how this works with a real Shell
			return new Shell();
		});

		$this->doStuffWithTempDir(function (BaseTestCase $phpu, $dirName) {
			Configuration::configure($dirName);

			// Copy sample INI file to path Configuration will look for TestConfig INI
			copy(BART_DIR . '/test/etc/conf-parser.conf', $dirName . '/test.conf');

			$configs = new TestConfig(false);

			$phpu->assertEquals('black', $configs->color(), 'TestConfigs color');
			$phpu->assertEquals('Quail', $configs->wildGame(), 'TestConfigs wildGame');
		});
	}

	public function testUnderscoresSupported()
	{
		Diesel::registerInstantiator('\Bart\Shell', function () {
			// Actually want to see how this works with a real Shell
			return new Shell();
		});

		$this->doStuffWithTempDir(function (BaseTestCase $phpu, $dirName) {
			Configuration::configure($dirName);

			// Copy sample INI file to path Configuration will look for TestConfig INI
			copy(BART_DIR . '/test/etc/conf-parser.conf', $dirName . '/test_underscore.conf');

			$configs = new Test_Underscore_Config(false);

			$phpu->assertEquals(42, $configs->number(), 'Underscore Configs number');
			$phpu->assertEquals('Quail', $configs->wildGame(), 'Underscore Configs wildGame');
		});
	}

	public function testRequiredConfigsWhenMissing()
	{
		$configs = new TestConfig();
		$configs->configureForTesting(self::$sampleConfigs);

		$this->assertThrows(
			'Bart\Configuration\ConfigurationException',
			'No value set for required favorites.food',
			function () use ($configs) {
				$configs->food();
			});
	}

	public function testNotRequiredConfigsWhenMissingWithNullDefault()
	{
		$configs = new TestConfig();
		$configs->configureForTesting(self::$sampleConfigs);
		$this->assertNull($configs->speed(), 'Speed configuration');
	}

	public function testNumericWhenInt()
	{
		$configs = new TestConfig();
		$configs->configureForTesting(self::$sampleConfigs);
		$this->assertEquals(42, $configs->number(), 'Favorite number');
		$this->assertInternalType('int', $configs->number(), 'Favorite number internal type');
	}

	public function testNumericWhenNotInt()
	{
		$this->assertThrows(
			'\Bart\Configuration\ConfigurationTypeConversionException',
			'Non-numeric provided',
			function () {
				$configs = new TestConfig();
				$configs->configureForTesting(array('favorites' => array('number' => 'a string')));
				$configs->number();
			});
	}

	public function testGetBoolWhenLiteralTrue()
	{
		$favorites = parse_ini_string('has_favorites = true', false);
		$configs = new TestConfig();
		$configs->configureForTesting(array('favorites' => $favorites));
		$this->assertTrue($configs->hasFavorites(), 'hasFavorites');
	}

	public function testGetBoolWhenQuotedTrue()
	{
		// Use quotes around value
		$favorites = parse_ini_string('has_favorites = "true"', false);
		$configs = new TestConfig();
		$configs->configureForTesting(array('favorites' => $favorites));
		$this->assertTrue($configs->hasFavorites(), 'hasFavorites');
	}

	public function testGetBoolWhenYes()
	{
		// Unbelievable...
		$favorites = parse_ini_string('has_favorites = yes', false);
		$configs = new TestConfig();
		$configs->configureForTesting(array('favorites' => $favorites));
		$this->assertTrue($configs->hasFavorites(), 'hasFavorites');
	}

	public function testGetBoolWhenQuotedYes()
	{
		// Quoted value should be treated like a string
		$favorites = parse_ini_string('has_favorites = "yes"', false);
		$configs = new TestConfig();
		$configs->configureForTesting(array('favorites' => $favorites));
		$this->assertFalse($configs->hasFavorites(), 'hasFavorites');
	}

	public function testGetBoolWhen1()
	{
		// Amazing...
		$favorites = parse_ini_string('has_favorites = 1', false);
		$configs = new TestConfig();
		$configs->configureForTesting(array('favorites' => $favorites));
		$this->assertTrue($configs->hasFavorites(), 'hasFavorites');
	}

	public function testGetBoolWhenQuoted1()
	{
		// Quoted value should be treated like a string, but 1 is not
		$favorites = parse_ini_string('has_favorites = "1"', false);
		$configs = new TestConfig();
		$configs->configureForTesting(array('favorites' => $favorites));
		$this->assertTrue($configs->hasFavorites(), 'hasFavorites');
	}

	public function testGetBoolFalse()
	{
		$favorites = parse_ini_string('has_favorites = false', false);
		$configs = new TestConfig();
		$configs->configureForTesting(array('favorites' => $favorites));
		$this->assertFalse($configs->hasFavorites(), 'hasFavorites');
	}

	public function testGetArrayHandlesSingleElement()
	{
		$configs = new TestConfig();
		$configs->configureForTesting(array(
			'favorites' => array(
				'nicknames' => 'bubba velociraptor',
			),
		));

		$nicknames = $configs->nicknames();
		$this->assertEquals(array('bubba velociraptor'), $nicknames);
	}

	public function testGetArrayHandlesZeroOrOneSpace()
	{
		$configs = new TestConfig();
		$configs->configureForTesting(array(
			'favorites' => array(
				'nicknames' => 'bubba, hingle,mccringle',
			),
		));

		$nicknames = $configs->nicknames();
		$this->assertEquals(array('bubba', 'hingle', 'mccringle'), $nicknames);
	}

	public function testGetArrayIgnoresSpacesNotProcededByCommas()
	{
		$configs = new TestConfig();
		$configs->configureForTesting(array(
			'favorites' => array(
				'nicknames' => 'bubba, hingle mccringle',
			),
		));

		$nicknames = $configs->nicknames();
		$this->assertEquals(array('bubba', 'hingle mccringle'), $nicknames);
	}

	public function testGetUsername()
	{
		$this->shmockAndDieselify('\Bart\Shell', function ($shell) {
			$shell->get_effective_user_name()->once()->return_value('jbraynard');
		});

		$configs = new TestConfig();
		$username = $configs->username();
		$this->assertEquals('jbraynard', $username, 'username');

		// Should use cache and not call get_effective_user_name a second time
		$username = $configs->username();
		$this->assertEquals('jbraynard', $username, 'username');
	}

	public function testGetPassword()
	{
		$this->shmockAndDieselify('\Bart\Shell', function ($shell) {
			$shell->std_in_secret('Give us your password: ')->once()->return_value('iamgod');
		});

		$configs = new TestConfig();
		$password = $configs->password();
		$this->assertEquals('iamgod', $password, 'pwd');

		// Should use cache and not call std_in_secret again
		$password = $configs->password();
		$this->assertEquals('iamgod', $password, 'pwd');
	}

	public function testGetPasswodBetweenDifferentConfigClasses()
	{
		$this->shmockAndDieselify('\Bart\Shell', function ($shell) {
			$shell->std_in_secret('Give us your password: ')->once()->return_value('iamgod');
		});

		$configs = new TestConfig();
		$password = $configs->password();
		$this->assertEquals('iamgod', $password, 'pwd');

		// Completely separate configuration class; shares only static cache
		$pwConfigs = new TestPasswdConfig();
		// Should use cache from above and not call std_in_secret again
		$pwdPwd = $pwConfigs->password();
		$this->assertEquals('iamgod', $pwdPwd, 'pwd');
	}

	public function testGetSecret()
	{
		$this->shmockAndDieselify('\Bart\Shell', function ($shell) {
			$shell->std_in_secret()->once()->return_value('078-05-1120');
		});

		$configs = new TestConfig();
		$configs->configureForTesting([]);
		$social = $configs->getSocialSecurityNumber();
		$this->assertEquals('078-05-1120', $social, 'social');

		// Should use cache and not call std_in_secret a second time
		$social = $configs->getSocialSecurityNumber();
		$this->assertEquals('078-05-1120', $social, 'social');
	}

	public function testSecretsAreSharedBetweenInstances()
	{
		$this->shmockAndDieselify('\Bart\Shell', function ($shell)
		{
			// Only called *once*
			// The next time it should use static cache
			$shell->std_in_secret('What is your social?')->once()->return_value('078-05-1120');
			// Let all other methods flow through actual class
		});

		// do this with a real instance so that we can get confidence
		// ...that the $filePath and cache are being used
		$this->doStuffWithTempDir(function (BaseTestCase $phpu, $dirName) {
			Configuration::configure($dirName);

			// Copy sample INI file to path Configuration will look for TestConfig INI
			copy(BART_DIR . '/test/etc/conf-parser.conf', $dirName . '/test.conf');

			$configs = new TestConfig(false);
			$social = $configs->getSocialSecurityNumber();
			$this->assertEquals('078-05-1120', $social, 'social');

			// Create a new instance
			// The secret should be statically cached
			$configs2 = new TestConfig(false);
			$social2 = $configs2->getSocialSecurityNumber();
			$this->assertEquals('078-05-1120', $social2, 'social2');
		});
	}

	public static function assertREADME(BaseTestCase $phpu, $configurationClassName, $configFileName)
	{
		$phpu->shmockAndDieselify('\Bart\Shell', function($shell) {
			$shell->std_in_secret()->any()->return_value('secret');
			$shell->std_in()->any()->return_value('secret');
		});

		$phpu->doStuffWithTempDir(function (BaseTestCase $phpu, $dirName)
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

class TestConfig extends Configuration
{
	public function __construct($skipLoad = true)
	{
		if ($skipLoad) return;

		parent::__construct();
	}

	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public function README()
	{
		return 'Test configuration class';
	}

	public function wildGame()
	{
		return $this->getValue('favorites', 'wild_game<king_matthewa>');
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

	public function speed()
	{
		return $this->getValue('travel', 'speed', null, false);
	}

	public function nicknames()
	{
		return $this->getArray('favorites', 'nicknames');
	}

	public function hasFavorites()
	{
		return $this->getBool('favorites', 'has_favorites');
	}

	public function username()
	{
		return $this->getCurrentUsername();
	}

	public function password()
	{
		return $this->getCurrentPassword('Give us your password: ');
	}

	public function getSocialSecurityNumber()
	{
		return $this->getSecret('pii', 'sn', 'What is your social?');
	}

	/**
	 * @param array $configsArray The new internal configurations
	 */
	public function configureForTesting(array $configsArray)
	{
		$this->configurations = $configsArray;

		// Some rigmarole for any methods using updateRuntimeConfiguration
		$pathField = Reflection_Helper::get_property('Bart\Configuration\Configuration', 'filePath');
		$pathField->setValue($this, '');

		TestConfigurationsHelper::setConfigCache($configsArray);
	}
}

/**
 * Config class with several an underscores
 */
class Test_Underscore_Config extends TestConfig
{
}

class TestPasswdConfig extends Configuration
{
	public function README()
	{
		return 'Test configuration class';
	}

	public function __construct()
	{
		// Do not call parent constructor
	}

	public function password()
	{
		return $this->getCurrentPassword('Anything');
	}
}
