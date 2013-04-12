<?php
namespace Bart\Shell;

class CommandTest extends \Bart\BaseTestCase
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
		$c = new Command('echo %s %s %s %s', 'hello', 'world', 1, 2);
		$safeStr = self::getSafeCommandFrom($c);

		$this->assertEquals("echo 'hello' 'world' '1' '2'", $safeStr, 'safe command');
	}

	public function testEscapesSingleQuotes()
	{
		$c = new Command('echo %s', "joe's a baller");
		$safeStr = self::getSafeCommandFrom($c);

		$this->assertEquals("echo 'joe'\\''s a baller'", $safeStr, 'single quotes');
	}

	public function testDigitsNotSupported()
	{
		$c = new Command('echo %d %d %s', 42, 43, 108);
		$safeStr = self::getSafeCommandFrom($c);

		$this->assertEquals("echo 0 0 '108'", $safeStr, 'Safe string');
	}

	public function testWithUnsafeBackticks()
	{
		$c = new Command('echo %s', '`cat /etc/password`');
		$safeStr = self::getSafeCommandFrom($c);

		$this->assertEquals('echo \'`cat /etc/password`\'', $safeStr, 'safe command');
	}

	public function testWithUnsafeSubshell()
	{
		$c = new Command('echo %s', '$(cat /etc/password)');
		$safeStr = self::getSafeCommandFrom($c);

		$this->assertEquals("echo '\$(cat /etc/password)'", $safeStr, 'safe command');
	}

	public function testWithUnsafeEnvVariableArg()
	{
		$c = new Command('echo %s', '$variable');
		$safeStr = self::getSafeCommandFrom($c);

		$this->assertEquals("echo '\$variable'", $safeStr, 'safe command');
	}

	public function testShellAliasMethod()
	{
		$shell = new \Bart\Shell();
		$cActual = $shell->command('echo %s %s %d', 'hello', 'world', 42);

		$cExpected = new Command('echo %s %s %d', 'hello', 'world', 42);

		$safeActual = self::getSafeCommandFrom($cActual);
		$safeExpected = self::getSafeCommandFrom($cExpected);

		$this->assertEquals($safeExpected, $safeActual, 'safe commands');
	}

	private static function getSafeCommandFrom(Command $c)
	{
		$field = \Bart\Util\Reflection_Helper::get_property('Bart\Shell\Command', 'safeCommandStr');

		return $field->getValue($c);
	}
}

