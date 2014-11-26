<?php
namespace Bart\Git;
use Bart\GitException;
use Bart\Jira\JiraIssue;
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
	public function __toString()
	{
		return $this->hash;
	}

	/**
	 * @return string The basic commit log message
	 */
	public function message()
	{
		$result = $this->gitRoot->getCommandResult('show -s --no-color %s', $this->hash);

		if (!$result->wasOk()) {
			throw new GitException("Could not get contents of commit {$this}");
		}

		return $result->getOutput(true);
	}

	/**
	 * @param string $filePath the path to the file from the project root
	 * @return string The raw contents of the file
	 */
	public function rawFileContents($filePath)
	{
		$result = $this->gitRoot->getCommandResult('show %s:%s', $this->hash, $filePath);

		if (!$result->wasOk()) {
			throw new GitException("Could not get contents of {$filePath} at revision {$this}");
		}

		return $result->getOutput(true);
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
}
