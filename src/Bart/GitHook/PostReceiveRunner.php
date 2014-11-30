<?php
namespace Bart\GitHook;

/**
 * Runs all git hooks configured for post-receive
 */
class PostReceiveRunner extends GitHookRunner
{
	/**
	 * @return string Name of hook
	 */
	protected function hookName()
	{
		return 'post-receive';
	}

	/**
	 * @return bool If execution of all hook actions should halt if one fails
	 */
	protected function haltOnFailure()
	{
		return false;
	}

	/**
	 * @return \string[] FQCN's of each hook action for class hook
	 */
	protected function getHookActionNames()
	{
		return $this->configs->getPostReceiveHookActions();
	}
}
