<?php
namespace Bart\GitHook;

use Bart\BaseTestCase;
use Bart\Git\CommitTest;
use Bart\Jira\JiraIssue;

class JiraCommentTest extends BaseTestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->stubConfigs();
	}

	public function testNoJiras()
	{
		$this->shmockAndDieselify('\chobie\Jira\Api', function ($jiraClient) {
			$jiraClient->addComment()->never();
		}, true);

		$head = CommitTest::getStubCommit($this, 'HEAD', function ($head) {
			$head->jiras()->once()->return_value([]);
		});

		$jiraComment = new JiraComment();
		$jiraComment->run($head);

	}

	public function testOneJira()
	{
		$this->shmockAndDieselify('\chobie\Jira\Api', function($jiraClient){
			// Expect request to comment on JIRA referenced in commit with
			// ...the configured template string
			$jiraClient->addComment('TEST-123', 'merged HEAD')->once();
		}, true);

		$head = CommitTest::getStubCommit($this, 'HEAD', function ($head) {
			$head->jiras()->once()->return_value([
					new JiraIssue('TEST-123'),
				]);
		});

		$jiraComment = new JiraComment();
		$jiraComment->run($head);
	}

	/**
	 * Stub the expected configuration
	 * @return void
	 */
	private function stubConfigs()
	{
		$this->shmockAndDieselify('\Bart\Jira\JiraClientConfig', function ($jConfigs) {
			$jConfigs->baseURL()->once()->return_value('https://jira.example.com');
			$jConfigs->username()->once()->return_value('john');
			$jConfigs->password()->once()->return_value('haXor');
		}, true);
		$this->shmockAndDieselify('\Bart\GitHook\GitHookConfig', function ($hConfigs) {
			$hConfigs->jiraCommentTemplate()->once()->return_value('merged %s');
		}, true);
	}

}
 