<?php
namespace Bart\Configuration;
use Bart\BaseTestCase;
use Bart\Util\Reflection_Helper;

/**
 * Help reset configurations between tests
 */
class TestConfigurationsHelper
{
	/** @var \ReflectionProperty */
	private static $pathField;
	/** @var \ReflectionProperty */
	private static $configCacheField;

	/**
	 * @param string $path [Optional] Temporary path from which the Configuration framework
	 * should load files. This is useful when writing temp files to disk for funtional tests
	 */
	public static function reset($path = null)
	{
		if (!self::$pathField) {
			self::$pathField = Reflection_Helper::get_property('Bart\Configuration\Configuration', 'path');
			self::$configCacheField = Reflection_Helper::get_property('Bart\Configuration\Configuration', 'configCache');
		}

		self::$pathField->setValue(null, $path);
		self::$configCacheField->setValue(null, []);
	}

	/**
	 * Completely override the internal static configurations cache
	 * @param array $configs
	 */
	public static function setConfigCache(array $configs)
	{
		self::$configCacheField->setValue(null, ['' => $configs]);
	}

	/**
	 * Deduce name of configuration class from name of the test class that
	 * mixed in the trait
	 * @param string $fqcn The name of the test class, e.g. \Bart\GitHook\GitHookConfigTest
	 * @return string The name of the configuration class
	 */
	public static function deduceConfigClassName($fqcn)
	{
		$pos = strrpos($fqcn, '_Test');
		if ($pos === false) {
			$pos = strrpos($fqcn, 'Test');
		}

		return substr($fqcn, 0, $pos);
	}

	/**
	 * @internal
	 * Get the return value of the README() method for given class name
	 * @param BaseTestCase $phpu
	 * @param string $configurationClassName FQCN
	 * @return string The return value of $configurationClassName->README()
	 */
	public static function getReadme(BaseTestCase $phpu, $configurationClassName)
	{
		// Get access to local README method without invoking constructor
		$double = $phpu->getMockBuilder($configurationClassName)
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		return $double->README();
	}

	/**
	 * @internal
	 * For an instance of a Configuration class, execute each of its get methods and
	 * assert they each return a non-empty value
	 * @param Configuration $configs The instantiated instance, e.g. new GitHookConfig($commit)
	 * @param BaseTestCase $phpu
	 * @param string $configurationClassName
	 */
	public static function assertConfigurationGetters(Configuration $configs, BaseTestCase $phpu, $configurationClassName)
	{
		$logger = \Logger::getLogger(__CLASS__);
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
	}
}
