<?php
namespace Bart\GitHook;

use Bart\Configuration\ProjectConfiguration;

/**
 * Configurations for Git Hooks
 */
class GitHookConfig extends ProjectConfiguration
{
	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public function README()
	{
		return <<<README
[pre_receive]
hook_actions = '\Bart\GitHook\StopTheLineTravis'

[post_receive]
hook_actions = '\Bart\GitHook\JiraComment', '\Bart\GitHook\GerritAbandon'

[jira]
; Used by JiraComment Hook Action
; %s will be replaced with commit revision hash
comment_template = "Commit %s pushed to JIRA. See online at https://git.example.com/?h=%s"

README;
	}

	/**
	 * @return \string[] List of FQCN's of each GitHookAction to be run on pre-receive
	 */
	public function getPreReceiveHookActions()
	{
		return $this->getArray('pre_receive', 'hook_actions');
	}

	/**
	 * @return \string[] List of FQCN's of each GitHookAction to be run on post-receive
	 */
	public function getPostReceiveHookActions()
	{
		return $this->getArray('post_receive', 'hook_actions');
	}

	/**
	 * @return string Template string to be sent to sprintf
	 */
	public function jiraCommentTemplate()
	{
		return $this->getValue('jira', 'comment_template');
	}
}