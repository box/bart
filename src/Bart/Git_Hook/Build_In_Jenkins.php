<?php
namespace Bart\Git_Hook;

use Bart\Diesel;

/**
 * Create a build in jenkins for the latest commit. Following exceptions apply:
 * - If commit message contains "{deploy}" then enqueue the :deploy-job
 * instead of the normal job.
 * - If commit message contains "{nobuild, reason=*}", then no action is taken
 */
class Build_In_Jenkins extends GitHookAction
{
	public function __construct(array $conf, $gitDir, $repo)
	{
		$jenkins_conf = $conf['jenkins'];
		if (!array_key_exists('job_name', $jenkins_conf))
		{
			// Default to the repo for convenience
			$jenkins_conf['job_name'] = $repo;
		}

		parent::__construct($jenkins_conf, $gitDir, $repo);
	}

	public function run($commitHash)
	{
		$info = $this->git->get_pretty_email($commitHash);
		$msg = $info['subject'] . PHP_EOL . $info['message'];
		if (preg_match('/\{nobuild\:\s(\".+?\")\}/', $msg, $matches) > 0)
		{
			$reason = $matches[1];
			$this->logger->debug('Skipping build with message: ' . $reason);
			return;
		}

		$jobName = $this->hookConf['job_name'];
		// Default parameters that all jobs may use, but may otherwise ignore
		$params = array(
			'GIT_HASH' => $commitHash,
			'Project_Name' => $this->repo,
			'Requested_By' => $info['author'],
		);

		if (preg_match('/\{deploy\}/', $msg, $matches) > 0)
		{
			// Submit a deploy job for repo
			$jobName = $this->hookConf['deploy-job'];
			// For repos whose deploy job is one and the same as the integration job
			$params['DEPLOY'] = 'true';
		}

		/** @var \Bart\Jenkins\Job $job */
		$job = Diesel::create('Bart\Jenkins\Job',
				$this->hookConf['host'], $jobName);

		$job->start($params);
	}
}
