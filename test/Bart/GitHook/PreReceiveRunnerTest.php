<?php
namespace Bart\GitHook;

use Bart\Git\Commit;

class PreReceiveRunnerTest extends TestBase
{
	public function testBadClassNamesIgnored()
	{
		$this->shmockAndDieselify('\Bart\GitHook\GitHookConfig', function($configs) {
			$configs->disable_original_constructor();
			$configs->getPreReceiveHookActions()->once()->return_value(['\This\Class\DNE']);
		});

		$head = $this->shmock('Bart\Git\Commit', function($commit) {
			$commit->disable_original_constructor();
			$commit->__toString()->any()->return_value('HEAD');
		});

		$preReceive = new PreReceiveRunner($head);
		// This should pass with no side effects
		$preReceive->runAllActions();
	}

	public function testValidHookNameRuns()
	{
		$this->shmockAndDieselify('\Bart\GitHook\GitHookConfig', function($configs) {
			$configs->disable_original_constructor();
			$configs->getPreReceiveHookActions()->once()->return_value([
				'\Bart\GitHook\ForTesting',
				'\Bart\GitHook\ForTesting',
			]);
		});

		// Register a real test hook action instance
		$testHookAction = new ForTesting();
		$this->registerDiesel('\Bart\GitHook\ForTesting', $testHookAction);

		$head = $this->shmock('Bart\Git\Commit', function($commit) {
			$commit->disable_original_constructor();
			$commit->__toString()->any()->return_value('HEAD');
		});

		$preReceive = new PreReceiveRunner($head);
		// This should pass with no side effects
		$preReceive->runAllActions();

		$this->assertCount(2, $testHookAction->commits, 'List of commits run against hooks');
		$this->assertSame($head, $testHookAction->commits[0], 'Commit for hook');
		// "Second" hook configured to run for this commit
		$this->assertSame($head, $testHookAction->commits[1], 'Commit for hook');
	}
}

/**
 * Class ForTesting Basic Hook Action to let us inject ourselves into execution path
 * @package Bart\GitHook
 */
class ForTesting extends GitHookAction
{
	/** @var Commit[] List of each commit sent to run() method */
	public $commits = [];

	public function run(Commit $commit)
	{
		$this->commits[] = $commit;
	}
}
