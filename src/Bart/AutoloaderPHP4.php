<?php

/**
 * Autoloader for PHP4, non-namespaced style classes
 * Automatically stacks into the spl_register_autoload() method
 */
class AutoloaderPHP4
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
		$path = $root;
		$words = explode('_', $class_name);

		for ($i = 0; $i < count($words) - 1; $i ++)
		{
			// Does underscore represent directory or word separator in class name?
			if (file_exists($path . $words[$i]))
			{
				$path .= $words[$i] . '/';
			}
			else
			{
				$path .= $words[$i] . '_' ;
			}
		}

		$path = $path . $words[$i] . '.php';
		if (file_exists($path))
		{
			return $path;
		}

		// Couldn't find path to class in given load path root
		return null;
	}
}

// Global namespace, i.e. compatiable with PHP 4
spl_autoload_register('AutoloaderPHP4::autoload');
