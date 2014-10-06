<?php
namespace Bart\Git;

use Bart\BaseTestCase;
use Bart\Log4PHP;
use Bart\Shell\StubbedCommandResult;

class CommitTest extends BaseTestCase
{
	private $gitRoot;

	private function stubMessage($message)
	{
		$message = "commit a57a2664feafb26c61d269babc63b272ed87544d
Author: Benjamin VanEvery <bvanevery@box.com>
Date:   Sun Oct 5 10:51:28 2014 -0700

$message";

		$this->gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root) use ($message) {
			$resultStub = new StubbedCommandResult([$message], 0);

			$root->exec()->once()->return_value($resultStub);
		});
	}

	public function testMessage()
	{
		$output = 'Create GitCommit class';
		$this->stubMessage($output);
		$commit = new Commit($this->gitRoot, 'HEAD');

		$this->assertContains('a57a2664feafb26c61d269babc63b272ed87544d', $commit->message(), 'hash');
	}

	public function testJiras()
	{
		$this->stubMessage('Fix problems from BUG-42; introduced by changes for PROJECT-336');
		$commit = new Commit($this->gitRoot, 'HEAD');

		$jiras = $commit->jiras();
		$this->assertCount(2, $jiras, 'Jiras matched');
		$this->assertEquals('BUG-42', $jiras[0]->id(), '1st Jira ID');
		$this->assertEquals('PROJECT-336', $jiras[1]->id(), '2nd Jira ID');
	}
}
 