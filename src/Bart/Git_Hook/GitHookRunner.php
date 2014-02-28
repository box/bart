<?php
namespace Bart\Git_Hook;
use Bart\Diesel;

/**
 * Parse input from STDIN and run relevant hooks
 */
class GitHookRunner
{
	/** @var string Name of the git project */
	public $projectName;
	/** @var string Name of the git hook */
	public $hookName;

	/**
	 * @param string $projectName
	 * @param string $hookName
	 */
	private function __construct($projectName, $hookName)
	{
		$this->projectName = $projectName;
		$this->hookName = $hookName;
	}

	/**
	 * @param string $invokedScript PHP SCRIPT_NAME e.g. hooks/post-recieve.d/bart-hook-runner
	 * @return GitHookRunner
	 * @throws GitHookException If the info can't be determined from script name
	 */
	public static function createFromScriptName($invokedScript)
	{
		// Use directory name (e.g. hooks); the realpath of the invoked script is likely a symlink
		$dirOfScript = dirname($invokedScript);

		/** @var \Bart\Shell $shell */
		$shell = Diesel::create('\Bart\Shell');
		// e.g. /var/lib/gitosis/puppet.git/hooks/post-receive.d
		$fullPathToDir = $shell->realpath($dirOfScript);

		// Can always safely assume that project name immediately precedes hooks dir
		// ...for both local and upstreams repos
		$hooksPos = strpos($fullPathToDir, '.git/hooks');

		if ($hooksPos === false) {
			throw new GitHookException("Could not infer project from path $fullPathToDir");
		}

		$pathToRepo = substr($fullPathToDir, 0, $hooksPos);
		$projectName = basename($pathToRepo);

		// Conventionally assume that name of hooks directory matches hook name itself
		// e.g. hooks/pre-receive.d/
		$hookName = substr($fullPathToDir, $hooksPos + strlen('.git/hooks/'));
		$hookName = substr($hookName, 0, strpos($hookName, '.'));

		return new self($projectName, $hookName);
	}
}
