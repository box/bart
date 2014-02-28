<?php
namespace Bart\Git_Hook;

use Bart\BaseTestCase;

class GitHookRunnerTest extends BaseTestCase
{
	public function testScriptNameParsing()
	{
		$stubShell = $this->getMock('\Bart\Shell');
		$stubShell->expects($this->once())
			->method('realpath')
			->with('hook/post-receive.d')
			->will($this->returnValue('/var/lib/gitosis/monty.git/hooks/post-receive.d'));

		$this->registerDiesel('\Bart\Shell', $stubShell);

		$runner = GitHookRunner::createFromScriptName('hook/post-recieve.d/bart-runner');
		$this->assertEquals('monty', $runner->projectName, 'project');
		$this->assertEquals('post-receive', $runner->hookName, 'hookName');

	}
}

