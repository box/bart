<?php
namespace Bart\Shell;

use Bart\BaseTestCase;
use Bart\Shell;

class CommandTest extends BaseTestCase
{
	/**
	 * @param BaseTestCase $testCase
	 * @param array $output
	 * @param int $statusCode
	 * @return Command A stub configured with $output and $statuCode
	 */
	public static function withStubbedResult(BaseTestCase $testCase, $output, $statusCode)
	{
		$resultStub = new StubbedCommandResult($output, $statusCode);
		return $testCase->shmock('Bart\Shell\Command', function($cmd) use ($resultStub) {
			$cmd->disable_original_constructor();
			$cmd->getResult()->once()->return_value($resultStub);
		});
	}

	public function testItCanRun()
	{
		$c = new Command('hostname');
		$hostname = $c->run();

		$expected = array(trim(shell_exec('hostname')));
		$this->assertEquals($expected, $hostname, 'hostname from exec');
	}

	public function testCommandStringEscaped()
	{
		$c = new Command('echo %s; echo %s; echo %s; echo %s', 1, 2, 3, 4);
		$output = $c->run(true);

		$this->assertEquals("1; echo 2; echo 3; echo 4", $output, 'command with ;');
	}

	public function testNewLinesEscaped()
	{
		$c = new Command('echo "%s
%s
%s"', 1, 2, 3);
		$output = $c->run();

		$this->assertEquals(array("'1''2''3'"), $output, 'escaped new lines');
	}

	public function testMultiLineCommand()
	{
		$c = new Command('php --version');
		$outputArray = $c->run();

		$this->assertGreaterThan(1, count($outputArray), 'Output array');

		$outputString = $c->run(true);
		$this->assertInternalType('string', $outputString, 'output string');
		$this->assertEquals(implode("\n", $outputArray), $outputString, 'imploded array');
	}

	public function testItHandlesFailureGracefully()
	{
		$this->assertThrows('Bart\Shell\CommandException', 'Got bad status', function()
		{
			$c = new Command('exit 1');
			$c->run();
		});
	}

	public function testExecuteHelloWorld()
	{
		$c = new Command('echo hello world');
		$hello = $c->getResult();

		$this->assertTrue($hello->wasOk(), 'passed');
		$this->assertEquals('hello world', $hello->getOutput(true), 'output');
		$this->assertEquals(['hello world'], $hello->getOutput(), 'output');
	}

	public function testExecuteDoesNotRaiseExceptionWhenBadStatus()
	{
		$fails = new Command('exit 1');
		$failed = $fails->getResult();

		$this->assertFalse($failed->wasOk(), 'Fails failed?');
	}

	public function testWithMoreThanOneArgument()
	{
		// @note digits treated as strings
		$safeStr = Command::makeSafeString('echo %s %s %s %s', array('hello', 'world', 1, 2));

		$this->assertEquals("echo 'hello' 'world' '1' '2'", $safeStr, 'safe command');
	}

	public function testEscapesSingleQuotes()
	{
		$safeStr = Command::makeSafeString('echo %s', array("joe's a baller"));;

		$this->assertEquals("echo 'joe'\\''s a baller'", $safeStr, 'single quotes');
	}

	public function testDigitsNotSupported()
	{
		$safeStr = Command::makeSafeString('echo %d %d %s', array(42, 43, 108));

		$this->assertEquals("echo 0 0 '108'", $safeStr, 'Safe string');
	}

	public function testWithUnsafeBackticks()
	{
		$safeStr = Command::makeSafeString('echo %s', array('`cat /etc/password`'));

		$this->assertEquals('echo \'`cat /etc/password`\'', $safeStr, 'safe command');
	}

	public function testWithUnsafeSubshell()
	{
		$safeStr = Command::makeSafeString('echo %s', array('$(cat /etc/password)'));

		$this->assertEquals("echo '\$(cat /etc/password)'", $safeStr, 'safe command');
	}

	public function testWithUnsafeEnvVariableArg()
	{
		$safeStr = Command::makeSafeString('echo %s', array('$variable'));

		$this->assertEquals("echo '\$variable'", $safeStr, 'safe command');
	}
}

