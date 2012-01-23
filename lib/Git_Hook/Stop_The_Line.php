<?php
/**
 * Prevent commits from progressing if they are not fixing a broken build
 */
class Git_Hook_Stop_The_Line extends Git_Hook_Base
{
	private $job;

	public function __construct(array $stl_conf, $git_dir, $repo, Witness $w, Diesel $di = null)
	{
		parent::__construct($stl_conf, $git_dir, $repo, $w, $di);

		if (!array_key_exists('job_name', $stl_conf))
		{
			// Default to the repo for convenience
			$stl_conf['job_name'] = $repo;
		}

		$this->job = $di->create($this, 'Jenkins_Job', array(
			'host' => $stl_conf['host'],
			'job_name' => $stl_conf['job_name'],
			'w' => $w,
		));
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

		throw new Exception('Jenkins not healthy');
	}
}

