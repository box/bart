<?php
namespace Bart\Git_Hook;

use Bart\Diesel;

class Stop_The_Line_Test extends TestBase
{
	private static $conf = array(
		'jenkins' => array(
			'host' => 'jenkins.host.com',
		));

	public function test_green_jenkins_job_with_configurable_job_name()
	{
		$conf = self::$conf;
		$conf['jenkins']['job_name'] = 'jenkins php unit job';

		$stlg = $this->configure_for($conf, true, $conf['jenkins']['job_name']);
		$stlg['stl']->run('hash');
	}

	public function test_green_jenkins_job_with_default_job_name()
	{
		$stlg = $this->configure_for(self::$conf, true, 'Gorg');
		$stlg['stl']->run('hash');
	}

	public function test_commit_msg_does_not_contain_buildfix()
	{
		$stlg = $this->configure_for(self::$conf, false, 'Gorg');
		$stl = $stlg['stl'];

		$mock_git = $stlg['git'];
		$mock_git->expects($this->once())
			->method('get_commit_msg')
			->with($this->equalTo('hash'))
			->will($this->returnValue('The commit message'));

		$this->assertThrows('\Exception', 'Jenkins not healthy', function() use($stl) {
			$stl->run('hash');
		});
	}

	public function test_multi_line_commit_msg_contains_buildfix()
	{
		$msg = 'some message
			some more messages

			and then again, a few others

			{buildfix}

			It happened in Monterey';

		$stlg = $this->configure_for(self::$conf, false, 'Gorg');
		$stl = $stlg['stl'];

		$mock_git = $stlg['git'];
		$mock_git->expects($this->once())
			->method('get_commit_msg')
			->with($this->equalTo('hash'))
			->will($this->returnValue($msg));

		$stl->run('hash');
	}

	private function configure_for($conf, $is_healthy, $job_name)
	{
		$mock_job = $this->getMock('\\Bart\\Jenkins\\Job', array(), array(), '', false);

		$mock_job->expects($this->once())
			->method('is_healthy')
			->will($this->returnValue($is_healthy));

		$gitStub = $this->getGitStub();

		$phpu = $this;
		Diesel::registerInstantiator('Bart\Jenkins\Job',
			function($host, $jobNameParam) use($phpu, $conf, $job_name, $mock_job) {
				$phpu->assertEquals($job_name, $jobNameParam,
						'Jenkins job name');

				$phpu->assertEquals($conf['jenkins']['host'], $host,
						'Jenkins host');

				return $mock_job;
		});

		return array(
			'stl' => new Stop_The_Line($conf, '', 'Gorg'),
			'git' => $gitStub,
		);
	}
}

