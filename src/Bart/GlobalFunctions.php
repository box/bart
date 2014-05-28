<?php
namespace Bart;

/**
 * Injection intermediary for global php functions
 * Similar to @see \Bart\Diesel.
 * Be aware that many global functions have class wrappers that collect related functions and it is preferrable
 * to use these when possible; e.g. @see \Bart\Shell
 *
 * Sample usage:
 * <pre>
 * // Sleep for 5 seconds
 * GlobalFunctions::sleep(5);
 * </pre>
 */
final class GlobalFunctions
{
	/** @var array {name => callable} All stubbed methods */
	private static $registry = [];
	/** @var boolean Permit instantiation if no record of requested class exists */
	private static $allowDefaults = true;

	/**
	 * @return mixed Result of calling $name-ed function
	 */
	public static function __callStatic($name, $args)
	{
		if (array_key_exists($name, self::$registry)) {
			$fn = self::$registry[$name];
			return call_user_func_array($fn, $args);
		}
		else if (!self::$allowDefaults) {
			throw new GlobalFunctionsException("No method stub registered for $name");
		}

		return call_user_func_array($name, $args);
	}

	/**
	 * @param string $name Name of function to stub
	 * @param callable $function Actual function to run instead of $name
	 * @throws GlobalFunctionsException If a method is already registered for $name
	 */
	public static function register($name, callable $function)
	{
		if (array_key_exists($name, self::$registry)) {
			// Common reasons this happens:
			// - someone overrides the test class setUp() and forgets to call the parent method
			// - confusion
			throw new GlobalFunctionsException("A method stub is already registered for $name");
		}

		self::$registry[$name] = $function;
	}

	/**
	 * Reset registry of method stubs
	 */
	public static function reset()
	{
		self::$registry = array();
	}

	/**
	 * Require that a method or singleton is defined for all instantiation
	 * @testonly
	 */
	public static function disableDefault()
	{
		self::$allowDefaults = false;
	}
}

class GlobalFunctionsException extends \Exception
{
}
