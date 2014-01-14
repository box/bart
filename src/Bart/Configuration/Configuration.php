<?php
namespace Bart\Configuration;
use Bart\Diesel;

/**
 * Configuration base. All configuration classes must extend this.
 * All children are required to define a README method which can be used
 * to see how each class expects its conf files to look.
 */
abstract class Configuration
{
	private static $path = null;
	private static $configCache = array();
	/** @var array */
	protected $configurations;

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

	public function __construct()
	{
		$this->load();
	}

	/**
	 * @param string $section
	 * @param string $key
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
	 * Load the configurations from the config file for subclass
	 * @return array The parsed array from the configuration file
	 */
	private function load()
	{
		if (!self::$path) {
			throw new ConfigurationException('Configuration root path not set! Please call configure()');
		}

		$subclass = get_called_class();

		$ind_slash = strrpos($subclass, '\\');
		if ($ind_slash !== false) {
			// Strip off namespace
			$subclass = substr($subclass, $ind_slash + 1);
		}

		// Strip off "Config"
		$subclass = substr($subclass, 0, -1 * strlen('Config'));
		// Chop any trailing underscore for non-camel cased names
		$name = strtolower(chop($subclass, '_'));

		$filePath = self::$path . "/$name.conf";

		if (!array_key_exists($filePath, self::$configCache)) {
			self::$configCache[$filePath] = $this->loadConfigurationsFromDisk($filePath, $name);
		}

		$this->configurations = self::$configCache[$filePath];
	}

	/**
	 * @return array
	 * @throws ConfigurationException
	 */
	private function loadConfigurationsFromDisk($filePath, $subclass)
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

class ConfigurationException extends \Exception
{
}

class ConfigurationTypeConversionException extends \Exception
{
}
