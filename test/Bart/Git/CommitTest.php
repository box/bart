<?php
namespace Bart\Git;

use Bart\BaseTestCase;
use Bart\Log4PHP;
use Bart\Shell\StubbedCommandResult;

class CommitTest extends BaseTestCase
{
	public function testMessage()
	{
		$gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root) {
			$output = 'commit a57a2664feafb26c61d269babc63b272ed87544d
Author: Benjamin VanEvery <bvanevery@box.com>
Date:   Sun Oct 5 10:51:28 2014 -0700

    Create GitCommit class

    Still need to add some tests {wip}';
			$resultStub = new StubbedCommandResult([$output], 0);

			$root->exec()->once()->return_value($resultStub);
		});

		$commit = new Commit($gitRoot, 'HEAD');

		$this->assertContains('a57a2664feafb26c61d269babc63b272ed87544d', $commit->message(), 'hash');
	}
}
 