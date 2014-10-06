<?php
namespace Bart\Git_Hook;
use Bart\Diesel;
use Bart\Git\Commit;
use \chobie\Jira\Api\Authentication\Basic;

/**
 * Adds comment to JIRA
 */
class JiraComment extends GitHookAction
{
	/** @var \chobie\Jira\Api */
	private $jiraClient;

	public function __construct($hookConf, $gitDir, $repo)
	{
		parent::__construct($hookConf, $gitDir, $repo);

		/** @var \Bart\Jira\JiraClientConfig $configs */
		$configs = Diesel::create('\Bart\Jira\JiraClientConfig');

		$this->jiraClient = Diesel::create('\chobie\Jira\Api',
			$configs->baseURL(), new Basic($configs->username(), $configs->password()));
	}

	/**
	 * Add a comment with the commit hash
	 * @param $commitHash string of commit to verify
	 * @throws GitHookException if requirement fails
	 */
	public function run(Commit $commit)
	{
		// TODO We probably need to create a whole framework for working with Git
		// TODO ...and send GitCommit objects to run()
		$this->jiraClient->addComment($commit->jira()->id(), "I have no idea what to say! $commit");
	}
}