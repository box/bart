<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Witness;
use Bart\Jenkins;

/**
 * Prevent commits from progressing if they are not fixing a broken build
 */
class Stop_The_Line extends Base
{
	private $job;

	public function __construct(array $conf, $git_dir, $repo, Witness $w)
	{
		$stl_conf = $conf['jenkins'];
		if (!array_key_exists('job_name', $stl_conf))
		{
			// Default to the repo for convenience
			$stl_conf['job_name'] = $repo;
		}

		parent::__construct($stl_conf, $git_dir, $repo, $w);

		$this->job = Diesel::create('Bart\Jenkins\Job',
				$stl_conf['host'], $stl_conf['job_name'], $w);
	}

	public function verify($commit_hash)
	{
		if ($this->job->is_healthy())
		{
			$this->w->report('Jenkins job is healthy.');
			return;
		}

		// Check if commit has hash
		$this->w->report('Jenkins job is not healthy...', null, false);
		$this->w->report('asserting that commit message contains {buildfix} hash');

		$msg = $this->git->get_commit_msg($commit_hash);
		if (preg_match('/\{buildfix\}/', $msg) > 0)
		{
			// Commit attempts to fix the build
			return;
		}

		throw new \Exception('Jenkins not healthy');
	}
}
