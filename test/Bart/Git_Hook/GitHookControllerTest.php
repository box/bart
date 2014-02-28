<?php
namespace Bart\Git_Hook;

use Bart\BaseTestCase;

class GitHookControllerTest extends BaseTestCase
{
	public function testScriptNameParsing()
	{
		$stubShell = $this->getMock('\Bart\Shell');
		$stubShell->expects($this->once())
			->method('realpath')
			->with('hook/post-receive.d')
			->will($this->returnValue('/var/lib/gitosis/monty.git/hooks/post-receive.d'));

		$this->registerDiesel('\Bart\Shell', $stubShell);

		$runner = GitHookController::createFromScriptName('hook/post-receive.d/bart-runner');
		$this->assertEquals('monty.post-receive', "$runner", 'hook runner to string');
	}
}

