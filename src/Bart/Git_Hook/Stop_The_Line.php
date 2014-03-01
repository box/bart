<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Jenkins;

/**
 * Reject commits against broken line unless the commit is fixing the build
 */
class Stop_The_Line extends GitHookAction
{
	private $job;

	public function __construct(array $conf, $gitDir, $repo)
	{
		$stl_conf = $conf['jenkins'];
		if (!array_key_exists('job_name', $stl_conf))
		{
			// Default to the repo for convenience
			$stl_conf['job_name'] = $repo;
		}

		parent::__construct($stl_conf, $gitDir, $repo);

		$this->job = Diesel::create('Bart\Jenkins\Job',
				$stl_conf['host'], $stl_conf['job_name']);
	}

	public function run($commitHash)
	{
		if ($this->job->is_healthy())
		{
			$this->logger->debug('Jenkins job is healthy.');
			return;
		}

		$this->logger->info('Jenkins job is not healthy...asserting that commit message contains {buildfix} hash');

		// Check if commit has buildfix directive
		$msg = $this->git->get_commit_msg($commitHash);
		if (preg_match('/\{buildfix\}/', $msg) > 0)
		{
			// Commit attempts to fix the build
			return;
		}

		throw new GitHookException('Jenkins not healthy and commit does not fix it');
	}
}
