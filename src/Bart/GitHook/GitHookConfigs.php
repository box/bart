<?php
namespace Bart\GitHook;

use Bart\Configuration\ProjectConfiguration;

/**
 * Configurations for Git Hooks
 */
class GitHookConfigs extends ProjectConfiguration
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
}