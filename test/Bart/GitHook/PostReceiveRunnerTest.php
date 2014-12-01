<?php
namespace Bart\GitHook;

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
			$configs->disable_original_constructor();
			$configs->getPostReceiveHookActions()->once()->return_value(['\This\Class\DNE']);
		});

		$head = $this->shmock('Bart\Git\Commit', function($commit) {
			$commit->disable_original_constructor();
			$commit->__toString()->any()->return_value('HEAD');
		});

		$preReceive = new PostReceiveRunner($head);
		// This should pass with no side effects
		$preReceive->runAllActions();
	}

}
