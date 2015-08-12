<?php
namespace Bart\Git;

use Bart\Jira\JiraIssue;
use Bart\Shell;

/**
 * Interacting with a Git Commit
 */
class Commit
{
	/** @var \Bart\Git\GitRoot */
	private $gitRoot;
	/** @var string The revision name of this commit */
	private $revision;
	/** @var JiraIssue[] Any Jira Issues mentioned in commit message */
	private $_jiras;

	/**
	 * @param string $revision The revision label of commit, typically the hash.
	 * See `man git-rev-parse` "SPECIFYING REVISIONS" for valid names
	 */
	public function __construct(GitRoot $root, $revision)
	{
		// Consider switching from composition of gitRoot to inheritance
		$this->gitRoot = $root;
		$this->revision = $revision;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->revision;
	}

	/**
	 * @return string Revision revision
	 */
	public function revision()
	{
		return $this->revision;
	}

	/**
	 * @return string The basic commit log message
	 */
	public function message()
	{
		$result = $this->gitRoot->getCommandResult('show -s --format=full --no-color %s', $this->revision);

		if (!$result->wasOk()) {
			throw new GitException("Could not get contents of commit {$this}");
		}

		return $result->getOutput(true);
	}

	/** 
	 * @return string[] The file names changed in commit
	 */
	public function getFileList()
	{
		$result = $this->gitRoot->getCommandResult('show --pretty="format:" --name-only %s', $this->revision);

		if (!$result->wasOk()) {
			throw new GitException("Could not get file names from commit {$this}");
		}

		return $result->getOutput(true);
	}

	/**
	 * @return string Gerrit Change-Id
	 * @throws GitException if no Change-Id in message
	 */
	public function gerritChangeId()
	{
		$message = $this->message();

		$matches = array();
		preg_match("/.*Change-Id: ([Ia-z0-9]*)/", $message, $matches);

		if (count($matches) === 0)
		{
			throw new GitException("No Change-Id in commit message for {$this}");
		}

		return $matches[1];
	}

	/**
	 * @param string $filePath the path to the file from the project root
	 * @return string The raw contents of the file
	 */
	public function rawFileContents($filePath)
	{
		$result = $this->gitRoot->getCommandResult('show %s:%s', $this->revision, $filePath);

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
			$this->_jiras = [];

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
