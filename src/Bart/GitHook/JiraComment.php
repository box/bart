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
		parent::__construct();

		/** @var \Bart\Jira\JiraClientConfig $jConfigs */
		$jConfigs = Diesel::create('\Bart\Jira\JiraClientConfig');

		$this->jiraClient = Diesel::create('\chobie\Jira\Api',
			$jConfigs->baseURL(), new Basic($jConfigs->username(), $jConfigs->password()));
	}

	/**
	 * Add a comment in JIRA with the commit hash
	 * @param Commit $commit The commit for which we're running the Git Hook
	 * @throws GitHookException if requirement fails
	 */
	public function run(Commit $commit)
	{
		/** @var \Bart\GitHook\GitHookConfig $hConfigs */
		$hConfigs = Diesel::create('\Bart\GitHook\GitHookConfig', $commit);

		// Apply template to produce desired comment for JIRA issue
		$template = $hConfigs->jiraCommentTemplate();
		$count = preg_match_all('/\%s/', $template);

		$this->logger->debug("Loaded jira template --$template-- and found $count token(s)");
		$vsprintf_args = [];
		if ($count !== false) {
			$vsprintf_args = array_fill(0, $count, $commit->revision());
		}

		$comment = vsprintf($template, $vsprintf_args);

		$jiraIssues = $commit->jiras();
		$this->logger->debug('Found ' . count($jiraIssues) . " jira issue(s) in $commit");
		foreach ($jiraIssues as $jira) {
			$this->logger->debug("Adding comment to jira {$jira}");
			$this->jiraClient->addComment($jira->id(), $comment);
		}
	}
}
