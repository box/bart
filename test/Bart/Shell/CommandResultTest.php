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

