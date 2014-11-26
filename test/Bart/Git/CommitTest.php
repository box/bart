<?php
namespace Bart\Git;

use Bart\BaseTestCase;
use Bart\Diesel;
use Bart\Log4PHP;
use Bart\Shell\StubbedCommandResult;
use Bart\Shell;

class CommitTest extends BaseTestCase
{
	private $gitRoot;

	/**
	 * Sets up GitRoot stub to expect to be asked for this message at HEAD
	 * @param string $message
	 */
	private function stubGitRootForMessage($message)
	{
		$message = "commit a57a2664feafb26c61d269babc63b272ed87544d
Author: Benjamin VanEvery <bvanevery@box.com>
Date:   Sun Oct 5 10:51:28 2014 -0700

$message";

		$this->gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root) use ($message) {
			$resultStub = new StubbedCommandResult([$message], 0);

			$root->getCommandResult('show -s --no-color %s', 'HEAD')->once()->return_value($resultStub);
		});
	}

	public function testMessage()
	{
		$output = 'Create GitCommit class';
		$this->stubGitRootForMessage($output);
		$commit = new Commit($this->gitRoot, 'HEAD');

		$this->assertContains('a57a2664feafb26c61d269babc63b272ed87544d', $commit->message(), 'hash');
	}

	public function testJiras()
	{
		$this->stubGitRootForMessage('Fix problems from BUG-42; introduced by changes for PROJECT-336');
		$commit = new Commit($this->gitRoot, 'HEAD');

		$jiras = $commit->jiras();
		$this->assertCount(2, $jiras, 'Jiras matched');
		$this->assertEquals('BUG-42', $jiras[0]->id(), '1st Jira ID');
		$this->assertEquals('PROJECT-336', $jiras[1]->id(), '2nd Jira ID');
	}

	public function testRawFileContentsStubbed()
	{
		$expectedContents = "password = h@x0r\nusername = god";
		$gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root) use ($expectedContents) {
			$resultStub = new StubbedCommandResult([$expectedContents], 0);

			$root->getCommandResult('show %s:%s', 'HEAD', 'secrets.txt')->once()->return_value($resultStub);
		});

		$commit = new Commit($gitRoot, 'HEAD');
		$actualContents = $commit->rawFileContents('secrets.txt');
		$this->assertEquals($expectedContents, $actualContents, 'Raw file contents');
	}

	/**
	 * @integrationTest
	 */
	public function testRawFileContentsReal()
	{
		// Let's make sure the command we're running is legit
		$expectedContents = trim(shell_exec('git show HEAD:composer.json'));

		// Replicate the actual shell invocation that would take place
		Diesel::registerInstantiator('Bart\Shell\Command', function() {
			$shell = new Shell();
			$args = func_get_args();
			return call_user_func_array([$shell, 'command'], $args);
		});
		$commit = new Commit(new GitRoot(BART_DIR . '/.git'), 'HEAD');

		$actualContents = trim($commit->rawFileContents('composer.json'));
		$this->assertEquals($expectedContents, $actualContents, 'Raw file contents');
	}
}
 