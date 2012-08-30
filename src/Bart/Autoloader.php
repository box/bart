<?php
namespace Bart;

/**
 * Autoload classes in defined load paths
 * Automatically stacks into the spl_register_autoload() method
 *
 * Default to class hierarchy in $BART_DIR/src
 */
class Autoloader
{
	// All paths associated with autloading
	private static $load_paths = array();

	/**
	 * Register a root path for autload to start its search
	 * Autoload will always look in the default load paths
	 */
	public static function register_autoload_path($path)
	{
		self::__static__construct();

		self::$load_paths[] = $path;
	}

	/**
	 * Static initializer
	 */
	private static function __static__construct()
	{
		// Should only be run once
		if (count(self::$load_paths) > 0) return;

		// By default, look in our src tree
		self::$load_paths[] = dirname(__DIR__);
	}

	/**
	 * Autoload function
	 */
	public static function autoload($class_name)
	{
		self::__static__construct();

		foreach (self::$load_paths as $load_path)
		{
			$path = self::path_to_class($load_path, $class_name);

			// Null signifies that the class is unknown to this load path
			// ...so try again in another load path
			if ($path === null) continue;

			require_once $path;
			return;
		}

		// Class wasn't found!
		// ...let PHP throw an error or try next autoload method
	}

	/**
	 * Given a class, return the path to the class
	 * @param $root root directory from which to start namespace
	 * @param $class_name Name of class referenced, but not yet in scope
	 */
	private static function path_to_class($root, $class_name)
	{
		$namespace_parts = explode('\\', $class_name);

		$path = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $namespace_parts) . '.php';
		if (file_exists($path))
		{
			return $path;
		}

		// Couldn't find path to class in given load path root
		return null;
	}
}

spl_autoload_register('\Bart\Autoloader::autoload');
