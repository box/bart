<?php
namespace Bart\Configuration;

use Bart\Diesel;
use Bart\Git\Commit;
use Bart\GitException;
use Bart\Primitives\Arrays;

/**
 * Configuration base. All configuration classes must extend this.
 * All children are required to define a README method which can be used
 * to see how each class expects its conf files to look.
 */
abstract class Configuration
{
	/** @var string The base directory containing all static configurations */
	private static $path = null;
	private static $configCache = array();
	/** @var array */
	protected $configurations;
	/** @var string File path on disk whence configuration file was loaded */
	private $filePath;

	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public abstract function README();

	/**
	 * @param string $path Root path to all configuration
	 * @throws ConfigurationException If already configured
	 */
	public static function configure($path)
	{
		if (self::$path) {
			// If this becomes innapppropriate, it can be lifted
			// It feels apppropriate based on my current understanding of use cases
			throw new ConfigurationException('Cannot reconfigure configuration path. Already set to ' . self::$path);
		}

		self::$path = $path;
	}

	/**
	 * Instantiate instance configured to load configurations based on called class name
	 */
	public function __construct()
	{
		$this->load();
	}

	/**
	 * @param string $section Configuration section in file
	 * @param string $key Configuration key in section
	 * @param mixed $default If not required, the default to use
	 * @param bool $required If an exception should be raised when value is missing
	 * @return mixed Configured value or default
	 * @throws ConfigurationException
	 */
	protected function getValue($section, $key, $default = null, $required = true)
	{
		if (array_key_exists($section, $this->configurations)) {
			$sectionValues = $this->configurations[$section];

			if (array_key_exists($key, $sectionValues)) {
				return $sectionValues[$key];
			}
		}

		// Complain when the value is required and no default passed
		// ...Provides path for non-required when the default is literally null
		if ($default === null && $required) {
			throw new ConfigurationException("No value set for required ${section}.${key}");
		}

		return $default;
	}

	/**
	 * http://stackoverflow.com/questions/12650802/php-equivalent-of-javascripts-parseint-function
	 */
	protected function getNumeric($section, $key, $default = null, $required = true)
	{
		$rawVal = $this->getValue($section, $key, $default, $required);

		if (ctype_digit($rawVal)) {
			return intval($rawVal);
		}

		if (is_numeric($rawVal)) {
			return $rawVal;
		}

		throw new ConfigurationTypeConversionException("Non-numeric provided for ${section}.${key}");
	}

	/**
	 * @return string[] String list of value split by comma
	 * @throws ConfigurationTypeConversionException
	 */
	protected function getArray($section, $key, array $default = null, $required = true)
	{
		$rawVal = $this->getValue($section, $key, $default, $required);

		// I considered accepting a "split" parameter, but decided that for now
		// ...enforcing a convention of at most one space after the comma will
		// ...encourage cleaner configuration files
		return preg_split('/,(\s)?/', $rawVal);
	}

	/**
	 * @return bool If the value equals the literal string "true"
	 */
	protected function getBool($section, $key, $default = null, $required = true)
	{
		$value = $this->getValue($section, $key, $default, $required);
		// will equal 'true' when conf is quoted, will equal '1' when literal boolean used!
		// See the unit tests for more fun realities of parse_ini_*()
		return ($value === 'true' || $value === '1');
	}

	/**
	 * @return string User name of effective user
	 */
	protected function getCurrentUsername()
	{
		// Assuming its safe to statically cache since only one user should be running the program
		if (!Arrays::vod(self::$configCache, '__USERNAME__')) {
			/** @var \Bart\Shell $shell */
			$shell = Diesel::create('\Bart\Shell');
			self::$configCache['__USERNAME__'] = $shell->get_effective_user_name();
		}

		return self::$configCache['__USERNAME__'];
	}

	/**
	 * Prompt user for their user account's password
	 * @seealso self::getSecret() for context specific secrets
	 * @param string $prompt Text to prompt the user
	 * @return string Current user's password
	 */
	protected function getCurrentPassword($prompt)
	{
		// Assuming its safe to statically cache since only one user should be running the program
		// ...and user should have only one local account & password
		if (!Arrays::vod(self::$configCache, '__PASSWD__')) {
			/** @var \Bart\Shell $shell */
			$shell = Diesel::create('\Bart\Shell');
			self::$configCache['__PASSWD__'] = $shell->std_in_secret($prompt);
		}

		return self::$configCache['__PASSWD__'];
	}

	/**
	 * Prompt the user for secret input. Secret is cached in $section.$key for later retrieval.
	 * @seealso self::getCurrentPassword() for globally shared password
	 * @param string $section Section in which to use key to save secret in cache only
	 * @param string $key Key name to associate with secret in cache only
	 * @param string $prompt Text to prompt user input
	 * @return string Secret input from user
	 */
	protected function getSecret($section, $key, $prompt)
	{
		$cached = $this->getValue($section, $key, null, false);

		// If we already prompted for the value
		if ($cached) {
			return $cached;
		}

		/** @var \Bart\Shell $shell */
		$shell = Diesel::create('\Bart\Shell');
		$secret = $shell->std_in_secret($prompt);

		$this->updateRuntimeConfiguration($section, $key, $secret);

		return $secret;
	}

	/**
	 * Load the configurations from the config file for subclass
	 * @return array The parsed array from the configuration file
	 */
	private function load()
	{
		$basePath = $this->configsPath();
		if (!$basePath) {
			throw new ConfigurationException('Configuration root path not set! Please call configure()');
		}

		$subclass = get_called_class();

		$indSlash = strrpos($subclass, '\\');
		if ($indSlash !== false) {
			// Strip off namespace
			$subclass = substr($subclass, $indSlash + 1);
		}

		// Strip off "Config"
		$subclass = substr($subclass, 0, -1 * strlen('Config'));
		// Chop any trailing underscore for non-camel cased names
		$name = strtolower(chop($subclass, '_'));

		$filePath = $basePath . "/$name.conf";

		if (!array_key_exists($filePath, self::$configCache)) {
			self::$configCache[$filePath] = $this->loadParsedIni($filePath, $name);
		}

		$this->filePath = $filePath;
		$this->configurations = self::$configCache[$filePath];
	}

	/**
	 * Set in memory configs for $section[$key] = $value; this is used exclusively
	 * for caching secrets
	 * @param string $section
	 * @param string $key
	 * @param string $value
	 */
	private function updateRuntimeConfiguration($section, $key, $value)
	{
		$cache = self::$configCache[$this->filePath];

		if (!Arrays::vod($cache, $section)) {
			$cache[$section] = [];
		}

		// Update with the new value
		$cache[$section][$key] = $value;

		self::$configCache[$this->filePath] = $cache;
		$this->configurations = $cache;
	}

	/**
	 * @abstract
	 * @return string Path to configurations
	 */
	protected function configsPath()
	{
		return self::$path;
	}

	/**
	 * @abstract
	 * @param string $filePath Absolute path to file containing configurations
	 * @param string $subclass Name of the configuration class
	 * @return array Contents of configuration parsed as INI with sections
	 * @throws ConfigurationException
	 */
	protected function loadParsedIni($filePath, $subclass)
	{
		/** @var \Bart\Shell $shell */
		$shell = Diesel::create('\Bart\Shell');
		if (!$shell->file_exists($filePath)) {
			throw new ConfigurationException("No configuration file found for $subclass at $filePath");
		}

		// @NOTE we're not using the ConfigResolver to resolve environment
		// ...distinctions by default. To add this ability, a new method should
		// ...be added to this base to resolve and then reset @configurations
		return $shell->parse_ini_file($filePath, true);
	}
}

/**
 * Class ConfigurationException Generic exception in Configuration package
 * @package Bart\Configuration
 */
class ConfigurationException extends \Exception
{
}

/**
 * Class ConfigurationTypeConversionException Exception when loading config value
 * and attempting coercion to expected type
 * @package Bart\Configuration
 */
class ConfigurationTypeConversionException extends \Exception
{
}
