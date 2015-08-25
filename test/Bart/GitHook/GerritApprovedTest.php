<?php
namespace Bart\GitHook;

use Bart\BaseTestCase;
use Bart\Git\CommitTest;
use Bart\Git\GitException;

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
		$this->shmockAndDieselify('\Bart\Gerrit\Change', function($change) {
			$change->isReviewedAndVerified()
				->once()
				->return_true();
		}, true);

		$hookAction = new GerritApproved();
		$hookAction->run($this->head);
	}

	public function testApprovedChangeNotFound()
	{
		$this->shmockAndDieselify('\Bart\Gerrit\Change', function($change) {
			$change->isReviewedAndVerified()
				->once()
				->return_false();
		}, true);

		$this->assertThrows('\Bart\GitHook\GitHookException', $this->changeId, function() {
			$hookAction = new GerritApproved();
			$hookAction->run($this->head);
		});
	}
}
