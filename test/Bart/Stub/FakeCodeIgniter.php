<?php
namespace Bart\Stub;

/**
 * Our own stub around code-igniter://system/core/Controller.php
 */
class FakeCodeIgniter
{
	/** @var FakeCodeIgniter */
	private static $instance;
	public $config;

	private function __construct(FakeCodeIgniterConfig $config)
	{
		$this->config = $config;
	}

	/**
	 * @param FakeCodeIgniterConfig $config Stub for ci $config
	 */
	public static function configureInstance(FakeCodeIgniterConfig $config)
	{
		self::$instance = new self($config);
	}

	public static function reset()
	{
		self::$instance = null;
	}

	/**
	 * Just like the original! @see CI_Controller
	 * @return FakeCodeIgniter
	 */
	public static function getInstance()
	{
		return self::$instance;
	}
}

/**
 * Mock this out
 */
class FakeCodeIgniterConfig
{
	public function item($name)
	{
		return;
	}
}
