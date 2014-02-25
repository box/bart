<?php
namespace Bart\Shell;

use Bart\BaseTestCase;
use Bart\Shell;

class CommandTest extends BaseTestCase
{
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

	public function testShellAliasMethod()
	{
		$shell = new Shell();
		$cActual = $shell->command('echo %s %s %d', 'hello', 'world', 42);

		$safeActual = self::getSafeCommandFrom($cActual);
		$safeExpected = Command::makeSafeString('echo %s %s %d', array('hello', 'world', 42));

		$this->assertEquals($safeExpected, $safeActual, 'safe commands');
	}

	private static function getSafeCommandFrom(Command $c)
	{
		$field = \Bart\Util\Reflection_Helper::get_property('Bart\Shell\Command', 'safeCommandStr');

		return $field->getValue($c);
	}
}

