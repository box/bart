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
			function() {
				$configs = new TestConfig();
				$configs->configureForTesting(array('favorites' => array('number' => 'a string')));
				$configs->number();
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

	/**
	 * @param array $configsArray The new internal configurations
	 */
	public function configureForTesting(array $configsArray)
	{
		$this->configurations = $configsArray;
	}

	public static function resetConfigurations()
	{
		if (!self::$pathField) {
			self::$pathField = Reflection_Helper::get_property('Bart\Configuration\Configuration', 'path');
			self::$configCacheField = Reflection_Helper::get_property('Bart\Configuration\Configuration', 'configCache');
		}

		self::$pathField->setValue(null, null);
		self::$configCacheField->setValue(null, array());
	}
}
