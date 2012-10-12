<?php
namespace Bart\Stub;

/**
 * PHPUnit (more specifically PHP) cannot handle mocking of methods that expect
 * variable references as arguments. So, it is necessary to do a little extra
 * work below to provide mocking of the Shell methods
 *
 * @Note sorry no fluid interface!
 */
class MockShell
{
	private $phpunit;
	private $command;
	private $output;
	private $return_var;
	private $return_val;
	private $configured = null;
	private $shell;

	/**
	 * Create a mock shell that will allow mocking of exec with reference variables
	 * @param \PHPUnit_Framework_TestCase $phpunit
	 * @param \Bart\Shell $shell Decorates a mock Shell instance if desired for missing methods
	 */
	public function __construct(\PHPUnit_Framework_TestCase $phpunit, \Bart\Shell $shell = null)
	{
		$this->phpunit = $phpunit;
		$this->shell = $shell;
	}

	/**
	 * Pass through all method calls to a shell to an internally mocked Shell instance
	 * This allows using the Mock_Shell in all use cases where a Shell would be expected
	 */
	public function __call($name, $args)
	{
		if (!isset($this->shell))
		{
			throw new \Exception("Call to undefined method $name. Try passing a Shell to constructor.");
		}

		return call_user_func_array(array($this->shell, $name), $args);
	}

	public function exec($command, &$output = null, &$return_var = null)
	{
		$this->assertConfigured('exec');
		$this->phpunit->assertEquals($this->command, $command,
			'Command did not match in mock exec');

		$output = $this->output;
		$return_var = $this->return_var;
		return $this->return_val;
	}

	public function passthru($command)
	{
		$this->assertConfigured('passthru');
		$this->phpunit->assertEquals($this->command, $command,
			'Command did not match in mock passthru');

		return $this->return_val;
	}

	/**
	 * Configure expected behavior of exec
	 */
	public function expect_exec($command, array $output, $return_var, $return_val)
	{
		$this->assertNotConfigured();
		$this->command = $command;
		$this->output = $output;
		$this->return_var = $return_var;
		$this->return_val = $return_val;
		$this->markConfigured('exec');
	}

	/**
	 * Configure expected behavior of passthru
	 */
	public function expect_passthru($command, $return_val)
	{
		$this->assertNotConfigured();
		$this->command = $command;
		$this->return_val = $return_val;
		$this->markConfigured('passthru');
	}

	/**
	 * @param string $for Method for which behavior has been mocked
	 */
	private function markConfigured($for)
	{
		$this->configured = $for;
	}

	private function assertNotConfigured()
	{
		$this->phpunit->assertNull($this->configured,
			"Mock_Shell already configured for {$this->configured}. Please create a new mock.");
	}

	private function assertConfigured($for)
	{
		$this->phpunit->assertEquals($for, $this->configured,
			"Mock_Shell was not configured for mocking $for");
	}
}

