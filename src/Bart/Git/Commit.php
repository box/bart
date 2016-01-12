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
	 * @return string The first non-blank lines of the commit message
	 * @throws GitException
	 */
	public function messageSubject()
	{
		return $this->gitShowFormatOutput('%s', 'Could not get contents of commit');
	}

	/**
	 * @requires Git 1.7.2
	 * @return string The unwrapped subject and body of the commit message (just the log message, not the author, etc.)
	 * @throws GitException
	 */
	public function messageRawBody()
	{
		return $this->gitShowFormatOutput('%B', 'Could not get contents of commit' );
	}

	/**
	 * @return string The full commit log message
	 */
	public function messageFull()
	{
		return $this->gitShowFormatOutput('full', 'Could not get contents of commit' );
	}

	/**
	 * @deprecated {@see self::messageFull()}
	 */
	public function message()
	{
		return $this->messageFull();
	}

	/**
	 * @return string Gerrit Change-Id
	 * @throws GitException if no Change-Id in message
	 */
	public function gerritChangeId()
	{
		$message = $this->messageFull();

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

			$message = $this->messageFull();

			$matches = [];
			if (preg_match_all('/([A-Z0-9]{1,12}-[1-9]?[0-9]*)/', $message, $matches) > 0) {
				foreach ($matches[1] as $match) {
					$this->_jiras[] = new JiraIssue($match);
				}
			}
		}

		return $this->_jiras;
	}

	/**
	 * The author of the commit (person who originally wrote the work)
	 * @return Person
	 * @throws GitException
	 */
	public function author()
	{
		$name = $this->gitShowFormatOutput('%aN', 'Could not get name of author');
		$email = $this->gitShowFormatOutput('%aE', 'Could not get email of author');
		return new Person($name, $email);
	}
	/**
	 * The committer of the commit (person who last applied the work)
	 * @return Person
	 * @throws GitException
	 */
	public function committer()
	{
		$name = $this->gitShowFormatOutput('%cN', 'Could not get name of committer');
		$email = $this->gitShowFormatOutput('%cE', 'Could not get email of committer');
		return new Person($name, $email);
	}

	/**
	 * The output from `git show` with a specific format (--format={$format} flag)
	 * and suppressed diff output (-s flag)
	 * @param string $format One of the formats specified in the "PRETTY FORMAT" section in the
	 * `git-show` man page (https://git-scm.com/docs/git-show)
	 * @param string $exceptionMsg The exception message to output in case the command fails
	 * @return string The output (as a string) of the `git show` command
	 * @throws GitException
	 */
	private function gitShowFormatOutput($format, $exceptionMsg)
	{
		$result = $this->gitRoot->getCommandResult("show -s --format='{$format}' --no-color {$this->revision}");
		if (!$result->wasOk()) {
			throw new GitException("{$exceptionMsg} at revision {$this}");
		}
		return $result->getOutput(true);
	}
}
