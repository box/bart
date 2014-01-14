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
		TestConfig::resetConfigurations();
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

	/**
	 * Asserts that the sample conf returned by README() on $configurationClassName
	 * can be parsed successfully by the class.
	 *
	 * This is a good practice to ensure that READMEs stay up to date with their class
	 *
	 * Technical details:
	 *    Will parse and use $configurationClassName::README() as the configuration
	 *    and call public methods on the class. Any missing or misconfigured
	 *    samples in the README will raise exceptions.
	 *
	 * @param BaseTestCase $case The current test instance running
	 * @param string $configurationClassName FQCN E.g. \Bart\Configuration\GerritConfig
	 * @param string $configFileName E.g. gerrit.conf
	 */
	public static function assertREADME(BaseTestCase $phpu, $configurationClassName, $configFileName)
	{
		Diesel::registerInstantiator('\Bart\Shell', function () {
			return new Shell();
		});

		$phpu->doStuffWithTempDir(function (BaseTestCase $phpu, $dirName)
		use ($configurationClassName, $configFileName) {
			$logger = \Logger::getLogger(__CLASS__);
			$logger->debug("Starting README test for $configurationClassName");

			// Setup cache so we can skip loading until we've written the actual configs
			// We need to do (something like) this because README() is not static
			TestConfig::resetConfigurations($dirName, $configFileName, array());
			$configs = new $configurationClassName();

			$logger->debug("Writing README to $configFileName");
			file_put_contents("{$dirName}/$configFileName", $configs->README());

			// Reset the cache so that the configs will be read from disk
			$logger->debug("Configuring system to load configs from $dirName");
			TestConfig::resetConfigurations($dirName);
			$configs = new $configurationClassName();

			$reflect = new \ReflectionClass($configurationClassName);
			$methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);

			$logger->debug("Iterating over relevant methods for $configurationClassName");
			foreach ($methods as $method) {
				if ($method->isStatic()) continue;
				if ($method->isConstructor()) continue;

				$logger->debug("Calling {$method->getName()} for $configurationClassName");
				$methodName = $method->getName();

				// Let's call the method! E.g. $gerritConfig::sshUser()
				$phpu->assertNotEmpty($configs->$methodName(), "$methodName()");
			}
		});
	}
}

class TestConfig extends Configuration
{
	private static $pathField;
	private static $configCacheField;

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

	/**
	 * @param array $configsArray The new internal configurations
	 */
	public function configureForTesting(array $configsArray)
	{
		$this->configurations = $configsArray;
	}

	public static function resetConfigurations($path = null, $confName = null, $value = null)
	{
		if (!self::$pathField) {
			self::$pathField = Reflection_Helper::get_property('Bart\Configuration\Configuration', 'path');
			self::$configCacheField = Reflection_Helper::get_property('Bart\Configuration\Configuration', 'configCache');
		}

		$cache = array();
		if ($confName) {
			$cache["$path/$confName"] = $value;
		}

		self::$pathField->setValue(null, $path);
		self::$configCacheField->setValue(null, $cache);
	}
}

/**
 * Config class with several an underscores
 */
class Test_Underscore_Config extends TestConfig
{
}
