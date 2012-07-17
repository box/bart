<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Witness;
use Bart\Jenkins\Job;

/**
 * Create a build in jenkins for the latest commit. Following exceptions apply:
 * - If commit message contains "{deploy}" then enqueue the :deploy-job
 * instead of the normal job.
 * - If commit message contains "{nobuild, reason=*}", then no action is taken
 */
class Build_In_Jenkins extends Base
{
	public function __construct(array $conf, $git_dir, $repo, Witness $w)
	{
		$jenkins_conf = $conf['jenkins'];
		if (!array_key_exists('job_name', $jenkins_conf))
		{
			// Default to the repo for convenience
			$jenkins_conf['job_name'] = $repo;
		}

		parent::__construct($jenkins_conf, $git_dir, $repo, $w);
	}

	public function verify($commit_hash)
	{
		$info = $this->git->get_pretty_email($commit_hash);
		$msg = $info['subject'] . PHP_EOL . $info['message'];
		if (preg_match('/\{nobuild\:\s(\".+?\")\}/', $msg, $matches) > 0)
		{
			$reason = $matches[1];
			$this->w->report('Skipping build with message: ' . $reason);
			return;
		}

		$jobName = $this->hook_conf['job_name'];
		// Default parameters that all jobs may use, but may otherwise ignore
		$params = array(
			'GIT_HASH' => $commit_hash,
			'Project_Name' => $this->repo,
			'Requested_By' => $info['author'],
		);

		if (preg_match('/\{deploy\}/', $msg, $matches) > 0)
		{
			// Submit a deploy job for repo
			$jobName = $this->hook_conf['deploy-job'];
			// For repos whose deploy job is one and the same as the integration job
			$params['DEPLOY'] = 'true';
		}

		// return new Job($params['host'], $params['job_name'], $params['w']);
		$job = Diesel::create('Bart\Jenkins\Job',
				$this->hook_conf['host'], $jobName, $this->w);

		$job->start($params);
	}
}
