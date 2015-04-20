<?php
namespace Bart\GitHook;
use Bart\Git\CommitTest;

/**
 * For other tests of GitHookRunner, see PreReceiveRunnerTest
 * Class PostReceiveRunnerTest
 * @package Bart\GitHook
 */
class PostReceiveRunnerTest extends TestBase
{
	public function testValidHookNameRuns()
	{
		$this->shmockAndDieselify('\Bart\GitHook\GitHookConfig', function($configs) {
			$configs->getPostReceiveHookActions()->once()->return_value(['\This\Class\DNE']);
		}, true);

		$head = CommitTest::getStubCommit($this);

		$preReceive = new PostReceiveRunner($head);
		// This should pass with no side effects
		$preReceive->runAllActions();
	}

}
