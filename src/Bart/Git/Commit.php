<?php
namespace Bart\Git;
use Bart\Jira\JiraIssue;
use Bart\Primitives\Arrays;
use Bart\Shell;

/**
 * Interacting with a Git Commit
 */
class Commit
{
	/** @var \Bart\Git\GitRoot */
	private $gitRoot;
	/** @var string The commit hash for this commit */
	private $hash;
	/** @var array[string] = string of properties of commit */
	private $props = [];
	/** @var JiraIssue[] Any Jira Issues mentioned in commit message */
	private $_jiras;

	/**
	 * @param string $hash
	 */
	public function __construct(GitRoot $root, $hash)
	{
		// Consider switching from composition of gitRoot to inheritance
		$this->gitRoot = $root;
		$this->hash = $hash;
	}

	/**
	 * @return string
	 */
	public function message()
	{
		return $this->properties('show -s --no-color %s');
	}

	/**
	 * @return JiraIssue[] Any matched Jira Issue from commit message
	 */
	public function jiras()
	{
		if ($this->_jiras === null) {
			$message = $this->message();

			$matches = [];
			if (preg_match_all('/([A-Z]{1,8}-[1-9]?[0-9]*)/', $message, $matches) > 0) {
				foreach ($matches[1] as $match) {
					$this->_jiras[] = new JiraIssue($match);
				}
			}
		}

		return $this->_jiras;
	}

	/**
	 * @param string $commandFmt
	 * @return mixed
	 */
	private function properties($commandFmt)
	{
		// Memoize the output of the commands
		if (!Arrays::vod($this->props, $commandFmt)) {
			$result = $this->gitRoot->exec($commandFmt, $this->hash);

			$this->props[$commandFmt] = $result->getOutput(true);
		}

		return $this->props[$commandFmt];
	}
}
