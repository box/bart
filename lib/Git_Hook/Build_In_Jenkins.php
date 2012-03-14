<?php
/**
 * Create a build in jenkins for the latest commit. Following exceptions apply:
 * - If commit message contains "{deploy}" then enqueue the :deploy job
 * instead of the normal job.
 * - If commit message contains "{nobuild, reason=*}", then no action is taken
 */
class Git_Hook_Build_In_Jenkins extends Git_Hook_Base
{
	public function __construct(array $conf, $git_dir, $repo, Witness $w, Diesel $di = null)
	{
		$jenkins_conf = $conf['jenkins'];
		if (!array_key_exists('job_name', $jenkins_conf))
		{
			// Default to the repo for convenience
			$jenkins_conf['job_name'] = $repo;
		}

		parent::__construct($jenkins_conf, $git_dir, $repo, $w, $di);
	}

	public static function dieselify($me)
	{
		parent::dieselify($me);

		Diesel::register_global($me, 'Jenkins_Job', function($params) {
			return new Jenkins_Job($params['host'], $params['job_name'], $params['w']);
		});
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

		$job = $this->hook_conf['job_name'];
		$params = array();
		if (preg_match('/\{deploy}/', $msg, $matches) > 0)
		{
			// Submit a deploy job for repo
			$params['Project-Name'] = $this->repo;
			$params['Requested-By'] = $info['author'];
			$job = $this->hook_conf['deploy-job'];
		}

		$job = $this->di->create($this, 'Jenkins_Job', array(
			'host' => $this->hook_conf['host'],
			'job_name' => $job,
			'w' => $this->w,
		));

		$job->start($params);
	}
}
