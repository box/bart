<?php
namespace Bart;

/**
 * Dependency Injection Epic Structured Extreme Language
 *
 * Statically configure runtime class to class dependencies or create one-off
 * dependency configurations for unit testing
 */
class Diesel
{
	/**
	 * @var array Registry of all instantiation methods
	 */
	private static $instantiators = array();
	/**
	 * @var array Registry of all singletons
	 */
	private static $singletons = array();
	// Global dependencies, should be registered by the class itself
	private static $registry = array();
	// List of classes that have been configured globally
	private static $dieselified = array();
	/**
	 * @var boolean Permit instantiation if no record of requested class exists
	 */
	private static $allowDefaults = true;

	// Used by a given instance of the factory
	private $local_registry = array();
	// For explicitly instantiating common classes, e.g. $diesel::Shell();
	private $magic_dependencies = array(
		'Shell' => 'Shell',
		'Git' => 'Git',
	);

	/**
	 * Create an instance of class
	 * @param string $className Name of the class
	 * @param array $arguments Any arguments needed by the class
	 * @return $className New instance of $className($arguments)
	 */
	public static function locateNew()
	{
		$arguments = func_get_args();
		$className = array_shift($arguments);

		// If a method has been registered
		if (array_key_exists($className, self::$instantiators)) {
			$instantiator = self::$instantiators[$className];

			return call_user_func_array($instantiator, $arguments);
		}

		return self::createInstance($className, $arguments);
	}

	/**
	 * Get singleton instance of this class
	 * @param type $className
	 * @return $className Singleton instance of class
	 */
	public static function singleton($className)
	{
		if (func_num_args() > 1) {
			throw new Exception('Diesel::singleton only accepts no-argument classes');
		}

		if (!array_key_exists($className, self::$singletons)) {
			self::$singletons[$className] = self::createInstance($className, array());
		}

		return self::$singletons[$className];
	}

	private static function createInstance($className, array $arguments)
	{
		if (!self::$allowDefaults) {
			throw new \Exception("No singleton or instantiator defined for $className");
		}

		if (count($arguments) == 0) {
			return new $className();
		}

		$class = new \ReflectionClass($className);
		return $class->newInstanceArgs($arguments);
	}

	/**
	 * Register the function to create an instance of $className
	 * @param string $className
	 * @param function $instantiator Function to create instance of $className
	 * @testonly
	 */
	public static function registerInstantiator($className, $instantiator)
	{
		if (!is_callable($instantiator)) {
			throw new \Exception('Only functions may be registered as instantiators');
		}

		if (array_key_exists($className, self::$instantiators)) {
			throw new \Exception("A function is already registered for $className");
		}

		self::$instantiators[$className] = $instantiator;
	}

	/**
	 * Register with the global registry. Used for static binding
	 * @param $owner The class type that will be requesting an instance of $class
	 * ...may be a string or an instance of the class
	 */
	public static function register_global($owner, $class, $instantiate)
	{
		$owner = self::get_class_name($owner);
		self::verify_owner_and_class(self::$registry, $owner, $class);

		self::$registry[$owner][$class] = $instantiate;
	}

	/**
	 * When unit testing, register a local override for a class
	 * @see register()
	 */
	public function register_local($owner, $class, $instantiate)
	{
		$owner = self::get_class_name($owner);
		self::verify_owner_and_class($this->local_registry, $owner, $class);

		$this->local_registry[$owner][$class] = $instantiate;
		$this->magic_dependencies[$class] = $instantiate;
	}

	/**
	 * Instantiates an object as registered for this $owner
	 *
	 * First looks in local registry for possible mocks, stubs, or overrides
	 */
	public function create($owner, $class, array $params = null, array &$refs = null)
	{
		$owner = self::get_class_name($owner);
		$instantiate = self::find(array($this->local_registry, self::$registry), $owner, $class, true);

		if (!is_callable($instantiate))
		{
			throw new \Exception ("No instantiation method defined for $owner dependency on $class");
		}

		return $instantiate($params, $refs);
	}

	/**
	 * Reset dependency map for all singletons, instantiators, and any methods for $owner
	 * @param string $owner [optional] Owning class of the dependency
	 */
	public static function reset($owner = '')
	{
		$owner = self::get_class_name($owner);
		if (array_key_exists($owner, self::$registry)) self::$registry[$owner] = array();

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

	/**
	 * Very commonly used class dependencies may be shared among all diesels and referenced
	 * using PHP magic method, e.g. $diesel->Shell()
	 */
	public function __call($name, $arguments)
	{
		if (array_key_exists($name, $this->magic_dependencies))
		{
			$classNameOrMockedMethod = $this->magic_dependencies[$name];

			if (is_callable($classNameOrMockedMethod))
			{
				// Unfortunately, we can't carry refs through; but perhaps that should
				// ...help discourage such practices
				return call_user_func($classNameOrMockedMethod, $arguments);
			}

			// TODO Spend some more time figuring out why this won't work with imports
			$classNameOrMockedMethod = '\\Bart\\'  . $classNameOrMockedMethod;
            if (count($arguments) == 0)
			{
				// TODO Conceivably, this could be optimized to share the same instance of
				// ...$classNameOrMockedMethod between Diesels for no args constructors
                return new $classNameOrMockedMethod();
            }
			else
			{
                $class = new \ReflectionClass($classNameOrMockedMethod);
                return $class->newInstanceArgs($arguments);
            }
		}
	}

	/**
	 * @param array $registries Diesel registries of instantiators
	 * @param type $owner Owner class by which the $class is needed
	 * @param type $class Class to be instantiated
	 * @param type $dieselify Automatically invoke $owner's dieselify method
	 * @return type Instantiation method for $class
	 */
	private static function find(array $registries, $owner, $class, $dieselify = false)
	{
		// Return the first instantiaion method found
		foreach ($registries as $registry)
		{
			if (array_key_exists($owner, $registry)
					&& array_key_exists($class, $registry[$owner]))
			{
				return $registry[$owner][$class];
			}
		}

		if ($dieselify && self::$allowDefaults)
		{
			self::dieselify($owner);
			return self::find(array(self::$registry), $owner, $class, false);
		}

		return null;
	}

	/**
	 * Call the static dieselify method on the owner class
	 */
	private static function dieselify($owner)
	{
		if (!empty(self::$dieselified[$owner])) return;

		// Somehow it got globally dieselified on its own
		if (array_key_exists($owner, self::$registry))
		{
			self::$dieselified[$owner] = true;
			return;
		}

		if (!class_exists($owner))
		{
			self::$dieselified[$owner] = false;
			return;
		}

		if (!method_exists($owner, 'dieselify'))
		{
			self::$dieselified[$owner] = false;
			return;
		}

		$owner::dieselify($owner);
		self::$dieselified[$owner] = true;
	}

	/**
	 * Verify registry array is set up and that we're not overwriting anything
	 */
	private static function verify_owner_and_class(&$registry, $owner, $class)
	{
		if (!array_key_exists($owner, $registry)) $registry[$owner] = array();

		if (array_key_exists($class, $registry[$owner]))
		{
			throw new \Exception("Creation method already registered for $owner and $class");
		}
	}

	private static function get_class_name($owner)
	{
		if (is_string($owner))
			return $owner;

		return get_class($owner);
	}
}
