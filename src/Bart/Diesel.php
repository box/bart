<?php
namespace Bart;

/**
 * Dependency Injection Epic Structured Extreme Language
 *
 * IoC container to manage singletons or non-static classes
 * http://github.com/box/bart
 */
class Diesel
{
	private static $isTestGeneratorRun = null;

	/**
	 * @var array Registry of all instantiation methods
	 */
	private static $instantiators = array();
	/**
	 * @var array Registry of all singletons
	 */
	private static $singletons = array();
	/**
	 * @var boolean Permit instantiation if no record of requested class exists
	 */
	private static $allowDefaults = true;

	/**
	 * Create an instance of class
	 * @param string $className Name of the class
	 * @param array $arguments Any arguments needed by the class
	 * @return $className New instance of $className($arguments)
	 */
	public static function create()
	{
		$arguments = func_get_args();
		$className = array_shift($arguments);

		$mockObj = self::checkTestScribe($className, $arguments);
		if ($mockObj !== null) {
			return $mockObj;
		}

		// If a method has been registered
		if (array_key_exists($className, self::$instantiators)) {
			$instantiator = self::$instantiators[$className];

			return call_user_func_array($instantiator, $arguments);
		}

		return self::createInstance($className, $arguments);
	}

	/**
	 * Return true when PHPUnit test generator is loaded.
	 * See comments in __callStatic method for more details.
	 *
	 * @return bool
	 */
	private static function isTestGeneratorRun()
	{
		if (self::$isTestGeneratorRun !== null) {
			// Cache the result of this run time check
			// to improve performance since resolve is called many times
			// at run time.
			return self::$isTestGeneratorRun;
		}

		// The test generator should have loaded the class and no autoloader should be needed
		// when the class exists.
		self::$isTestGeneratorRun = class_exists('\Box\TestScribe\App', false);

		return self::$isTestGeneratorRun;
	}

	/**
	 * Replace real instance with the given instance typically a mock object.
	 *
	 * @param string $className
	 * @param object $instance
	 *
	 * @return void
	 */
	public static function overwrite($className, $instance)
	{
		self::registerInstantiator(
			$className,
			function () use ($instance) {
				return $instance;
			}
		);
	}

	/**
	 * Get singleton instance of this class
	 * @param string $className
	 * @return $className Singleton instance of class
	 */
	public static function singleton($className)
	{
		if (func_num_args() > 1) {
			throw new DieselException('Diesel::singleton only accepts no-argument classes');
		}

		if (!array_key_exists($className, self::$singletons)) {
			self::$singletons[$className] = self::createInstance($className, array());
		}

		return self::$singletons[$className];
	}

	private static function createInstance($className, array $arguments)
	{
		if (!self::$allowDefaults) {
			throw new DieselException("No singleton or instantiator defined for $className");
		}

		$argCount = count($arguments);
		if ($argCount == 0) {
			return new $className();
		}

		$class = new \ReflectionClass($className);

		// LIMITATION: cannot pass by reference due to dynamic evaluation of
		// ...$arguments in create();
		return $class->newInstanceArgs($arguments);
	}

	/**
	 * Register the function to create an instance of $className
	 * @param string $className Name of class being injected
	 * @param callable $instantiator Function to create instance of $className
	 *        or existing instance to override singleton
	 * @param boolean $singleton Class is a singleton?
	 * @testonly
	 */
	public static function registerInstantiator($className, $instantiator, $singleton = false)
	{
		if ($singleton) {
			self::$singletons[$className] = $instantiator;
			return;
		}

		if (!class_exists($className)) {
			throw new DieselException("Cannot register instantiator for $className because it does not exist");
		}

		if (!is_callable($instantiator)) {
			throw new DieselException('Only functions may be registered as instantiators');
		}

		if (array_key_exists($className, self::$instantiators)) {
			throw new DieselException("A function is already registered for $className");
		}

		self::$instantiators[$className] = $instantiator;
	}

	/**
	 * Reset dependency map for all singletons and instantiators
	 */
	public static function reset()
	{
		self::$instantiators = array();
		self::$singletons = array();
	}

	/**
	 * Require that a method or singleton is defined for all instantiation
	 */
	public static function disableDefault()
	{
		self::$allowDefaults = false;
	}


	private static function checkTestScribe($className, $arguments)
	{

		/** @noinspection PhpUndefinedNamespaceInspection */
		/** @noinspection PhpUndefinedClassInspection */
		if (self::isTestGeneratorRun()
			&& \Box\TestScribe\App::$shouldInjectMockObjects
		) {
			// This code block is to support PHPUnit test generator.
			// @see https://github.com/box/TestScribe
			// The class 'Box\TestScribe\App' should only be loaded
			// when this method is executed by the test generator.
			// It allows the test generator to substitute the real class instance with a
			// mock object controlled by the test generator so that the test
			// generator can monitor the creation and execution of the class
			// being resolved.
			/** @noinspection PhpUndefinedNamespaceInspection */
			$mock = \Box\TestScribe\App::createMockedInstance(
				$className,
				$arguments
			);
			if ($mock) {
				return $mock;
			}
			// If null is returned by the unit test generator,
			// it means that the developer has decided not to mock this class.
			// Proceed with creating the real object.
		}
	}
}

class DieselException extends \Exception
{
}
