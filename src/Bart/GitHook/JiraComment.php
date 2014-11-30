<?php
namespace Bart\GitHook;
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

	/**
	 * Jira Comment Hook Action
	 */
	public function __construct()
	{
		/** @var \Bart\Jira\JiraClientConfig $configs */
		$configs = Diesel::create('\Bart\Jira\JiraClientConfig');

		$this->jiraClient = Diesel::create('\chobie\Jira\Api',
			$configs->baseURL(), new Basic($configs->username(), $configs->password()));
	}

	/**
	 * Add a comment in JIRA with the commit hash
	 * @param Commit $commit The commit for which we're running the Git Hook
	 * @throws GitHookException if requirement fails
	 */
	public function run(Commit $commit)
	{
		$configs = new GitHookConfig($this->commit);

		// Apply template to produce desired comment for JIRA issue
		$template = $configs->jiraCommentStem();
		$count = preg_match('/\%s/', $template);

		$vsprintf_args = [];
		if ($count !== false) {
			$vsprintf_args = array_fill(0, $count, $commit->revision());
		}

		$comment = vsprintf($template, $vsprintf_args);

		foreach ($commit->jiras() as $jira) {
			$this->logger->debug("Adding comment to jira {$jira}");
			$this->jiraClient->addComment($jira->id(), $comment);
		}
	}
}
