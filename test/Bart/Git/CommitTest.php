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

			$root->getCommandResult('show -s --format=full --no-color %s', 'HEAD')->once()->return_value($resultStub);
		});
	}

	private function stubGitRootFileList($getFileList)
	{
		$getFileList = "commit 31ac1584186257ce6aa14d9cefd78a4b2f89c90e
Author: Nishad Singh <nsingh@box.com>
Date:   Mon Aug 10 14:09:36 2015 -0700

conf_override/databases.conf
conf_override/features.conf
conf_override/services.conf

$getFileList";

		$this->gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root) use ($getFileList) {
			$resultStub = new StubbedCommandResult(array($getFileList), 0);

			$root->getCommandResult('show --pretty="format:" --name-only %s', 'HEAD')->once()->return_value($resultStub);
		});
	}

	public function testMessage()
	{
		$output = 'Create GitCommit class';
		$this->stubGitRootForMessage($output);
		$commit = new Commit($this->gitRoot, 'HEAD');

		$this->assertContains('a57a2664feafb26c61d269babc63b272ed87544d', $commit->message(), 'hash');
	}

	public function testGetFileList()
	{
		$output = 'Create GitCommit class';
		$this->stubGitRootFileList($output);
		$commit = new Commit($this->gitRoot, 'HEAD');

		$this->assertContains("conf_override/features.conf\nconf_override/services.conf", $commit->getFileList(), 'hash');
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

	/**
	 * Utility method to create a stub Commit for any tests that need one
	 * Custom expectations can be set via the $configure parameter
	 * @param BaseTestCase $phpu
	 * @param string $revision
	 * @param callable $configure Shmock configuration function
	 * @return mixed
	 */
	public static function getStubCommit(BaseTestCase $phpu, $revision = 'HEAD', callable $configure = null)
	{
		return $phpu->shmock('\Bart\Git\Commit', function($commit) use ($revision, $configure) {
			$commit->disable_original_constructor();
			$commit->__toString()->any()->return_value($revision);
			$commit->revision()->any()->return_value($revision);

			if ($configure) {
				$configure($commit);
			}
		});
	}
}
 