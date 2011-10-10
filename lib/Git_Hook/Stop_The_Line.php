<?php
/**
 * Prevent commits from progressing if they are not fixing a broken build
 */
class Git_Hook_Stop_The_Line
{
	private $job;
	private $witness;

	public function __construct($domain, $job, Witness $witness)
	{
		$this->witness = $witness;
		$this->job = new Jenkins_Job($domain, $job, $witness);
	}

	public function verify($msg)
	{
		if ($this->job->is_healthy())
		{
			return true;
		}

		// Check if commit has hash
		$this->witness->report('Job is not healthy...', null, false);
		$this->witness->report('asserting that commit message contains {buildfix} hash');

		return (preg_match('/\{buildfix\}/', $msg) > 0);
	}
}

