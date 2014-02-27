<?php
namespace Bart\Git_Hook;

use Bart\Diesel;

class Build_In_Jenkins_Test extends TestBase
{
	private static $repo = 'Gorgoroth';
	private static $author = 'Gollum';
	private static $conf = array(
		'jenkins' => array(
			'host' => 'jenkins.host.com',
			'deploy-job' => 'Vlad, The Deployer',
		));

	public function test_not_skipped_with_no_justification()
	{
		$msg = '{nobuild}';
		$jg = $this->configure_for(array('jenkins' => array()), $msg, self::$repo);

		// PHP will complain that the configuration is missing the jenkins host
		$this->assertThrows('\Exception', 'Undefined index: host', function() use ($jg) {
			$jg['j']->run('HEAD');
		});
	}

	public function test_git_skipped()
	{
		$msg = 'some message

			{nobuild: "Incrementing version number"}

			It happened in Monterey';
		$jg = $this->configure_for(array('jenkins' => array()), $msg, self::$repo);

		// Expect early return and error thrown on misconfiguration if failed
		$jg['j']->run('HEAD');
	}

	public function test_deploy_job()
	{
		$msg = 'some message {deploy} and more message';
		$jg = $this->configure_for(self::$conf, $msg, self::$conf['jenkins']['deploy-job']);
		$mock_job = $jg['job'];
		// Build should be submitted to jenkins to deploy the Gorg repository
		$mock_job->expects($this->once())
			->method('start')
			->with($this->equalTo(array(
				'Project_Name' => self::$repo,
				'Requested_By' => self::$author,
				'GIT_HASH' => 'HEAD',
				'DEPLOY' => 'true',
			)));

		// Expect a jenkins job created for Vlad
		$jg['j']->run('HEAD');
	}

	public function test_typical_commit()
	{
		$msg = 'some normal commit message';
		$jg = $this->configure_for(self::$conf, $msg, self::$repo);
		$mock_job = $jg['job'];
		// Build should be submitted for Gorg repo
		$mock_job->expects($this->once())
			->method('start')
			->with($this->equalTo(array(
				'Project_Name' => self::$repo,
				'Requested_By' => self::$author,
				'GIT_HASH' => 'HEAD',
			)));

		// Expect a jenkins job created for Vlad
		$jg['j']->run('HEAD');
	}

	/**
	 * Basic, shared setup for the Build_In_Jenkins hook
	 *
	 * @param array $conf Configurations for the hook
	 * @param string $commit_msg The commit message for the hook
	 * @param string $job_name The name of the job to be built
	 * @return array The git hook and the git stub
	 */
	private function configure_for(array $conf, $commit_msg, $job_name)
	{
		$hash = 'HEAD';
		$info = array(
			'author' => self::$author,
			'subject' => '',
			'message' => $commit_msg,
		);

		$mock_git = $this->getGitStub();
		$mock_git->expects($this->once())
			->method('get_pretty_email')
			->with($this->equalTo($hash))
			->will($this->returnValue($info));

		$phpu = $this;
		$mock_job = $this->getMock('\Bart\Jenkins\Job', array(), array(), '', false);
		Diesel::registerInstantiator('Bart\Jenkins\Job',
			function($host, $name) use($phpu, $conf, $job_name, $mock_job) {
				$phpu->assertEquals($job_name, $name,
						'Jenkins job name did not match');

				$phpu->assertEquals($conf['jenkins']['host'], $host,
						'Expected host to match conf');

				return $mock_job;
		});

		return array(
			'j' => new Build_In_Jenkins($conf, '', self::$repo),
			'git' => $mock_git,
			'job' => $mock_job,
		);
	}
}
