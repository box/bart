<?php
namespace Bart\Git;

use Bart\BaseTestCase;
use Bart\Diesel;
use Bart\Shell\CommandTest;

class GitRootTest extends BaseTestCase
{
	public function testBasicCommandExec()
	{
		$root = new GitRoot();

		$command = CommandTest::withStubbedResult($this, ['a57a266'], 0);
		Diesel::registerInstantiator('Bart\Shell\Command',
			function($fmt, $dir, $limit, $author, $pretty) use ($command) {
				// Assert that GitRoot sends expected parameters to create the Command
				$this->assertEquals('git --git-dir=%s log %s --author=%s --format:%s', $fmt, 'command arg 0');
				$this->assertEquals('.git', $dir);
				$this->assertEquals('-1', $limit, 'Limit of commits');
				$this->assertEquals('jbraynard', $author);
				$this->assertEquals('%h', $pretty, 'pretty format');

				return $command;
			});

		// Throw something together with a few args interspersed
		$result = $root->getCommandResult('log %s --author=%s --format:%s', '-1', 'jbraynard', '%h');

		$this->assertEquals('a57a266', $result->getOutput(true), 'git log');
	}
}
 