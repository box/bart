<?php
namespace Bart\Git;
use Bart\Shell;

/**
 * Base of a git repo
 */
class GitRoot
{
	/** @var string Path to root .git dir or bare checkout */
	private $dir;

	/**
	 * @param string $dir Path to root .git dir or bare checkout
	 */
	public function __construct($dir = '.git')
	{
		$this->dir = $dir;
	}

	/**
	 * @param string $commandFmt Git command to run in root git directory
	 * @param string $args, ... [Optional] All arguments to git command
	 * @return Shell\CommandResult
	 */
	public function getCommandResult($commandFmt)
	{
		$args = func_get_args();
		// Swap arg param 0 (i.e. $commandFmt) for the "--git-dir" arg
		$args[0] = $this->dir;

		$command = Shell\Command::fromFmtAndArgs("git --git-dir=%s $commandFmt", $args);
		return $command->getResult();
	}
}
