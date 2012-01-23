<?php
$path = dirname(dirname(__DIR__)) . '/';
require_once $path . 'setup.php';

class Git_Hook_Stop_The_Line_Test extends Bart_Base_Test_Case
{
	private static $conf = array(
			'host' => 'jenkins.host.com',
		);

	public function test_green_jenkins_job_with_configurable_job_name()
	{
		$conf = self::$conf;
		$conf['job_name'] = 'jenkins php unit job';

		$stlg = $this->configure_for($conf, true, $conf['job_name']);
		$stlg['stl']->verify('hash');
	}

	public function test_green_jenkins_job_with_default_job_name()
	{
		$stlg = $this->configure_for(self::$conf, true, 'Gorg');
		$stlg['stl']->verify('hash');
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

		$this->assert_throws('Exception', 'Jenkins not healthy', function() use($stl) {
			$stl->verify('hash');
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

		$stl->verify('hash');
	}

	private function configure_for($conf, $is_healthy, $job_name)
	{
		$mock_job = $this->getMock('Jenkins_Job', array(), array(), '', false);

		$mock_job->expects($this->once())
			->method('is_healthy')
			->will($this->returnValue($is_healthy));

		$dig = Git_Hook_Base_Test::get_diesel($this, 'Git_Hook_Stop_The_Line');
		$di = $dig['di'];

		$phpu = $this;
		$di->register_local('Git_Hook_Stop_The_Line', 'Jenkins_Job',
			function($params) use($phpu, $conf, $job_name, $mock_job) {
				$phpu->assertEquals($job_name, $params['job_name'],
						'Jenkins job name did not match');

				$phpu->assertEquals($conf['host'], $params['host'],
						'Expected host to match conf');

				return $mock_job;
		});

		$w = new Witness_Silent();
		return array(
			'stl' => new Git_Hook_Stop_The_Line($conf, '', 'Gorg', $w, $di),
			'git' => $dig['git'],
		);
	}
}

