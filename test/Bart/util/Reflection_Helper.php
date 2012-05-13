<?php
namespace Bart\Util;

/**
 * Assist with PHP Reflection
 * Cache reflection information for performance
 */
class Reflection_Helper
{
	private static $classes = array();

	public static function get_class($class_name)
	{
		if (array_key_exists($class_name, self::$classes))
		{
			return self::$classes[$class_name];
		}

		// Let this throw an error if the class doesn't exist
		$reflection = new \ReflectionClass($class_name);
		self::$classes[$class_name] = $reflection;

		return $reflection;
	}

	public static function get_method($class_name, $method_name)
	{
		$class = self::get_class($class_name);
		$method = $class->getMethod($method_name);
		$method->setAccessible(true);

		return $method;
	}

	public static function get_property($class_name, $prop_name)
	{
		$class = self::get_class($class_name);
		$prop = $class->getProperty($prop_name);
		$prop->setAccessible(true);

		return $prop;
	}
}

