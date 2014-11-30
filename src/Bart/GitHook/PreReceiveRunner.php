<?php
namespace Bart\GitHook;

/**
 * Runs all git hooks configured for pre-receive
 */
class PreReceiveRunner extends GitHookRunner
{
	/**
	 * @return string Name of hook
	 */
	protected function hookName()
	{
		return 'pre-receive';
	}

	/**
	 * @return bool If execution of all hook actions should halt if one fails
	 */
	protected function haltOnFailure()
	{
		return true;
	}

	/**
	 * @return \string[] FQCN's of each hook action for class hook
	 */
	protected function getHookActionNames()
	{
		return $this->configs->getPreReceiveHookActions();
	}
}
