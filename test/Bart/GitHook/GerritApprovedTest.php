<?php
namespace Bart\GitHook;

use Bart\BaseTestCase;
use Bart\Git\CommitTest;

class GerritApprovedTest extends BaseTestCase
{
	private $changeId = 'Iabcde123';
	private $commitHash = 'abcde123';
	private $head;

	public function setUp()
	{
		parent::setUp();

		$this->head = CommitTest::getStubCommit($this, $this->commitHash, function($commit) {
			$commit->gerritChangeId()->once()->return_value($this->changeId);
		});
	}

	public function testValidCommit()
	{
		$this->shmockAndDieselify('Bart\Gerrit\Api', function($api) {
			// Any value will do here, just can't be null
			$api->getApprovedChange($this->changeId, $this->commitHash)
				->once()
				->return_value(['id' => $this->changeId]);
		}, true);

		$hookAction = new GerritApproved();
		$hookAction->run($this->head);
	}

	public function testApprovedChangeNotFound()
	{
		$this->shmockAndDieselify('Bart\Gerrit\Api', function($api) {
			// Any value will do here, just can't be null
			$api->getApprovedChange($this->changeId, $this->commitHash)
				->once()
				->return_value(null);
		}, true);

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
			// Any value will do here, just can't be null
			$api->getApprovedChange($this->changeId, $this->commitHash)
				->once()
				->throw_exception(new \Exception('Invalid credentials'));
		}, true);

		$msg = 'Error getting Gerrit review info';
		$this->assertThrows('\Exception', $msg, function() {
			$hookAction = new GerritApproved();
			$hookAction->run($this->head);
		});
	}
}
