<?php
namespace Bart\Git;

use Bart\BaseTestCase;
use Bart\Diesel;
use Bart\Log4PHP;
use Bart\Shell\StubbedCommandResult;
use Bart\Shell;
use Bart\Util\Reflection_Helper;

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

			$root->getCommandResult("show -s --format='full' --no-color HEAD")->once()->return_value($resultStub);
		});
	}

	public function testMessageSubject()
	{
		// This test is pretty much a "it compiles" test
		$this->gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root) {
			$output = 'TOP-338 run unit tests when Nate or Zack commit code';
			$resultStub = new StubbedCommandResult([$output], 0);

			$root->getCommandResult("show -s --format='%s' --no-color HEAD")
				->once()
				->return_value($resultStub);
		});
		$commit = new Commit($this->gitRoot, 'HEAD');

		// Assert all lines of log message are included
		$message = $commit->messageSubject();
		$this->assertStringStartsWith('TOP-338', $message, 'No escape args silliness with quotes');
		$this->assertEquals('TOP-338 run unit tests when Nate or Zack commit code', $message, 'line one and only line one');
	}

	public function testMessageRawBody()
	{
		$this->gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root) {
			$output = 'TOP-338 run unit tests when Nate or Zack commit code

Change-Id: Iecb840ccccf70a79ae622c583761107aa1a1b7b9';
			$resultStub = new StubbedCommandResult([$output], 0);

			$root->getCommandResult("show -s --format='%B' --no-color HEAD")
				->once()
				->return_value($resultStub);
		});
		$commit = new Commit($this->gitRoot, 'HEAD');

		// Assert all lines of log message are included
		$message = $commit->messageRawBody();
		$this->assertStringStartsWith('TOP-338', $message, 'No escape args silliness with quotes');
		$this->assertContains('TOP-338 run unit', $message, 'line one');
		$this->assertContains('Iecb840ccccf70a79ae622c583761107aa1a1b7b9', $message, 'line two');
	}

	public function testMessageFull()
	{
		$output = 'Create GitCommit class';
		$this->stubGitRootForMessage($output);
		$commit = new Commit($this->gitRoot, 'HEAD');

		$this->assertContains('a57a2664feafb26c61d269babc63b272ed87544d', $commit->messageFull(), 'hash');
	}

	public function testCommitter()
	{
		$expectedName = 'Committer Name';
		$expectedEmail = 'committer@example.com';
		$gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root)
		use ($expectedName, $expectedEmail) {
			$nameResultStub = new StubbedCommandResult([$expectedName], 0);
			$emailResultStub = new StubbedCommandResult([$expectedEmail], 0);
			$root->order_matters();
			$root->getCommandResult("show -s --format='%cN' --no-color HEAD")
				->once()
				->return_value($nameResultStub);
			$root->getCommandResult("show -s --format='%cE' --no-color HEAD")
				->once()
				->return_value($emailResultStub);
		});

		$commit = new Commit($gitRoot, 'HEAD');
		$person = $commit->committer();
		$actualName = $person->getName();
		$actualEmail = $person->getEmail();
		$this->assertSame($expectedName, $actualName);
		$this->assertSame($expectedEmail, $actualEmail);
	}

	public function testAuthor()
	{
		$expectedName = 'Author Name';
		$expectedEmail = 'author@example.com';
		$gitRoot = $this->shmock('\Bart\Git\GitRoot', function($root)
		use ($expectedName, $expectedEmail) {
			$nameResultStub = new StubbedCommandResult([$expectedName], 0);
			$emailResultStub = new StubbedCommandResult([$expectedEmail], 0);
			$root->order_matters();
			$root->getCommandResult("show -s --format='%aN' --no-color HEAD")
				->once()
				->return_value($nameResultStub);
			$root->getCommandResult("show -s --format='%aE' --no-color HEAD")
				->once()
				->return_value($emailResultStub);
		});

		$commit = new Commit($gitRoot, 'HEAD');
		$person = $commit->author();
		$actualName = $person->getName();
		$actualEmail = $person->getEmail();
		$this->assertSame($expectedName, $actualName);
		$this->assertSame($expectedEmail, $actualEmail);
	}

	public function testJiras()
	{
		$this->stubGitRootForMessage("Fix problems from BUG-42; introduced by changes for PROJECT-336\nSee TEAM01-23 for more details");
		$commit = new Commit($this->gitRoot, 'HEAD');

		$jiras = $commit->jiras();
		$this->assertCount(3, $jiras, 'Jiras matched');
		$this->assertEquals('BUG-42', $jiras[0]->id(), '1st Jira ID');
		$this->assertEquals('PROJECT-336', $jiras[1]->id(), '2nd Jira ID');
		$this->assertEquals('TEAM01-23', $jiras[2]->id(), 'Jira ID with numbers');
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
 