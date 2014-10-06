<?php
namespace Bart\Shell;

use Bart\BaseTestCase;

class CommandResultTest extends BaseTestCase
{
	public function testCodeFailed()
	{
		$r = new CommandResult(new Command('echo'), [], 1);
		$this->assertFalse($r->wasOk(), 'status > 0');
	}

	public function testCodePassed()
	{
		$r = new CommandResult(new Command('echo'), [], 0);
		$this->assertTrue($r->wasOk(), 'status = 0');
	}

	public function testStubs()
	{
		$r = new StubbedCommandResult(['hello, world'], 0);
		$this->assertEquals('hello, world', $r->getOutput(true), 'output');
		$this->assertTrue($r->wasOk(), 'Passed?');
	}
}

/**
 * For stubbing results of Command::getResult()
 */
class StubbedCommandResult extends CommandResult
{
	/** @var Command */
	private static $_echoCmd;

	/**
	 * Create a stub of a command result for testing
	 * @param array $output The output of exec
	 * @param int $statusCode The shell return status
	 */
	public function __construct($output, $statusCode)
	{
		parent::__construct(self::echoCmd(), $output, $statusCode);
	}

	/**
	 * @return Command Just re-use this dummy command each time
	 */
	private static function echoCmd()
	{
		if (!self::$_echoCmd) {
			self::$_echoCmd = new Command('echo');
		}

		return self::$_echoCmd;
	}
}
 