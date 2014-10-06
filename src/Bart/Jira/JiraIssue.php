<?php
namespace Bart\Jira;

/**
 * A reference to a Jira Issue
 */
class JiraIssue
{
	/** @var string The Jira Identifier */
	private $id;

	/**
	 * @param string $id The Jira Identifier. E.g. "ISSUE-123"
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	public function id()
	{
		return $this->id;
	}
}
