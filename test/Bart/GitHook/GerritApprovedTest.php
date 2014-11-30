<?php
namespace Bart\GitHook;

use Bart\BaseTestCase;

class GerritApprovedTest extends BaseTestCase
{
	private $changeId = 'Iabcde123';
	private $commitHash = 'abcde123';
	private $head;

	public function setUp()
	{
		parent::setUp();

		$this->head = $this->shmock('Bart\Git\Commit', function($commit) {
			$commit->disable_original_constructor();
			$commit->gerritChangeId()->once()->return_value($this->changeId);
			$commit->revision()->once()->return_value($this->commitHash);
			$commit->__toString()->any()->return_value($this->commitHash);
		});
	}

	public function testValidCommit()
	{
		$this->shmockAndDieselify('Bart\Gerrit\Api', function($api) {
			$api->disable_original_constructor();
			// Any value will do here, just can't be null
			$api->getApprovedChange($this->changeId, $this->commitHash)
				->once()
				->return_value(['id' => $this->changeId]);
		});

		$hookAction = new GerritApproved();
		$hookAction->run($this->head);
	}

	public function testApprovedChangeNotFound()
	{
		$this->shmockAndDieselify('Bart\Gerrit\Api', function($api) {
			$api->disable_original_constructor();
			// Any value will do here, just can't be null
			$api->getApprovedChange($this->changeId, $this->commitHash)
				->once()
				->return_value(null);
		});

		$msg = "approved review was not found in Gerrit for commit {$this->commitHash} "
		. "with Change-Id {$this->changeId}";
		$this->assertThrows('\Exception', $msg, function() {
			$hookAction = new GerritApproved();
			$hookAction->run($this->head);
		});
	}

	public function testGerritException()
	{
		$this->shmockAndDieselify('Bart\Gerrit\Api', function($api) {
			$api->disable_original_constructor();
			// Any value will do here, just can't be null
			$api->getApprovedChange($this->changeId, $this->commitHash)
				->once()
				->throw_exception(new \Exception('Invalid credentials'));
		});

		$msg = 'Error getting Gerrit review info';
		$this->assertThrows('\Exception', $msg, function() {
			$hookAction = new GerritApproved();
			$hookAction->run($this->head);
		});
	}
}
