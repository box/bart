<?php
namespace Bart\Configuration;
use Bart\Diesel;

/**
 * Configuration base. All configuration classes must extend this.
 */
abstract class Configuration
{
	private static $path = null;
	private static $configCache = array();
	/** @var array */
	protected $configurations;

	/**
	 * @param string $path Root path to all configuration
	 * @throws Configuration_Exception If already configured
	 */
	public static function configure($path)
	{
		if (self::$path) {
			// If this becomes innapppropriate, it can be lifted
			// It feels apppropriate based on my current understanding of use cases
			throw new Configuration_Exception('Cannot reconfigure configuration path. Already set to ' . self::$path);
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
	 * @throws Configuration_Exception
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
			throw new Configuration_Exception("No value set for required ${section}.${key}");
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

		throw new Configuration_Type_Conversion_Exception("Non-numeric provided for ${section}.${key}");
	}

	/**
	 * Load the configurations from the config file for subclass
	 * @return array The parsed array from the configuration file
	 */
	private function load()
	{
		if (!self::$path) {
			throw new Configuration_Exception('Configuration root path not set! Please call configure()');
		}

		$subclass = get_called_class();
		// Strip off namespace
		$subclass = substr($subclass, strrpos($subclass, '\\') + 1);
		// Strip off "Config"
		$name = strtolower(basename($subclass, 'Config'));

		$filePath = self::$path . "/$name.conf";

		if (!array_key_exists($filePath, self::$configCache)) {
			self::$configCache[$filePath] = $this->load_configurations_from_disk($filePath, $subclass);
		}

		$this->configurations = self::$configCache[$filePath];
	}

	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public abstract function README();

	/**
	 * @return array
	 * @throws Configuration_Exception
	 */
	private function load_configurations_from_disk($filePath, $subclass)
	{
		/** @var \Bart\Shell $shell */
		$shell = Diesel::create('\Bart\Shell');
		if (!$shell->file_exists($filePath)) {
			throw new Configuration_Exception("No configuration file found for $subclass at $filePath");
		}

		// @NOTE we're not using the ConfigResolver to resolve environment
		// ...distinctions by default. To add this ability, a new method should
		// ...be added to this base to resolve and then reset @configurations
		return $shell->parse_ini_file($filePath, true);
	}
}

class Configuration_Exception extends \Exception
{
}

class Configuration_Type_Conversion_Exception extends \Exception
{
}
