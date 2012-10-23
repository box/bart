<?php
namespace Bart\Stub;

/**
 * MockShell provides the ability to assert the behavior of calls to both {@see Shell::exec()} and
 * {@see Shell::passthru()}.
 *
 * Due to the manner in which PHPUnit creates mocks expectations, it is not possible to generalize
 * the expectation of parameters passed by reference.
 *
 * TODO expect same command string more than once
 */
class MockShell
{
	private $phpunit;
	/** @var array[MockShellCommand] Keyed by command string, holds all expected exec commands */
	private $execs = array();
	/** @var array[MockShellCommand] Keyed by command string, holds all expected passthru commands */
	private $passthrus = array();
	private $shell;

	/**
	 * Creates a MockShell capable of mocking multiple calls to both {@see Shell::exec()} and
	 * {@see Shell::passthru()}
	 *
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
	 * This allows using the MockShell in all use cases where a Shell would be expected
	 */
	public function __call($name, $args)
	{
		if (!isset($this->shell))
		{
			throw new \Exception("Call to undefined method $name. Try passing a Shell to constructor.");
		}

		return call_user_func_array(array($this->shell, $name), $args);
	}

	/**
	 * @param string $cmdStr Command to execute
	 * @param array $output All output from command
	 * @param int $returnVar exit status
	 * @return string Last line of output
	 */
	public function exec($cmdStr, &$output = null, &$returnVar = null)
	{
		$this->assertConfigured($this->execs, $cmdStr);
		$command = $this->execs[$cmdStr];
		unset($this->execs[$cmdStr]);

		$output = $command->output;
		$returnVar = $command->exitStatus;

		return $command->returns;
	}

	/**
	 * @param string $cmdStr Command to passthru
	 * @param int $returnVar The exit status
	 * @return void
	 */
	public function passthru($cmdStr, &$returnVar)
	{
		$this->assertConfigured($this->passthrus, $cmdStr);
		$command = $this->passthrus[$cmdStr];
		unset($this->passthrus[$cmdStr]);

		$returnVar = $command->exitStatus;
	}

	/**
	 * Configure expected behavior of {@see Shell::exec()} to set the
	 * reference params and also return the last element of $output.
	 *
	 * @param string $cmdStr Command string associated to $output and $returnVar
	 * @param array $output Value to assign $output
	 * @param int $exitStatus Value to assign $exitStatus
	 * @return MockShell $this
	 */
	public function expectExec($cmdStr, array $output, $exitStatus)
	{
		$this->assertNotConfigured($this->execs, $cmdStr);
		$command = MockShellCommand::newExec($cmdStr, $output, $exitStatus);

		$this->execs[$cmdStr] = $command;

		return $this;
	}

	/**
	 * Configure expected behavior of {@see Shell::passthru()} to set the
	 * $returnVal reference params.
	 *
	 * @param string $cmdStr Command string associated with $returnVar
	 * @param int $returnVar exit status to assign to reference param
	 * @return MockShell $this
	 */
	public function expectPassthru($cmdStr, $exitStatus)
	{
		$this->assertNotConfigured($this->passthrus, $cmdStr);
		$command = MockShellCommand::newPassthru($cmdStr, $exitStatus);

		$this->passthrus[$cmdStr] = $command;

		return $this;
	}

	/**
	 * Verify the calls to exec and passthru were called as expected
	 * @throws Assertion Exception if otherwise
	 */
	public function verify()
	{
		$this->phpunit->assertEquals(
			0,
			count($this->execs) + count($this->passthrus),
			'Some MockShell commands not run');
	}

	private function assertNotConfigured(array $commands, $cmdStr)
	{
		$hasKey = array_key_exists($cmdStr, $commands);
		$this->phpunit->assertFalse($hasKey, "MockShell already configured for $cmdStr");
	}

	private function assertConfigured(array $commands, $cmdStr)
	{
		$hasKey = array_key_exists($cmdStr, $commands);
		$this->phpunit->assertTrue($hasKey, "MockShell not configured for $cmdStr");
	}
}

/**
 * A single command expected by MockShell
 */
final class MockShellCommand
{
	public $cmdStr, $output, $exitStatus, $returns;

	/**
	 * All parameters relate to either {@see exec()} or {@see passthru()}
	 */
	private function __construct($cmdStr, array $output = null, $exitStatus = null, $returns = null)
	{
		$this->cmdStr = $cmdStr;
		$this->output = $output;
		$this->exitStatus = $exitStatus;
		$this->returns = $returns;
	}

	/**
	 * @return MockShellCommand for an {@see exec()} call
	 */
	public static function newExec($cmdStr, array $output, $exitStatus)
	{
		$outputCount = count($output);
		$returns = $outputCount > 0 ? $output[$outputCount - 1] : '';

		return new self($cmdStr, $output, $exitStatus, $returns);
	}

	/**
	 * @return MockShellCommand for a {@see passthru()} call
	 */
	public static function newPassthru($cmdStr, $exitStatus)
	{
		return new self($cmdStr, null, $exitStatus);
	}
}

