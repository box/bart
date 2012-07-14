<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Witness;

class Stop_The_Line_Test extends \Bart\BaseTestCase
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

		$this->assertThrows('\Exception', 'Jenkins not healthy', function() use($stl) {
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
		$mock_job = $this->getMock('\\Bart\\Jenkins\\Job', array(), array(), '', false);

		$mock_job->expects($this->once())
			->method('is_healthy')
			->will($this->returnValue($is_healthy));

		$dig = Base_Test::get_diesel($this, 'Bart\\Git_Hook\\Stop_The_Line');
		$di = $dig['di'];

		$phpu = $this;
		$di->register_local('Bart\\Git_Hook\\Stop_The_Line', 'Jenkins_Job',
			function($params) use($phpu, $conf, $job_name, $mock_job) {
				$phpu->assertEquals($job_name, $params['job_name'],
						'Jenkins job name did not match');

				$phpu->assertEquals($conf['jenkins']['host'], $params['host'],
						'Expected host to match conf');

				return $mock_job;
		});

		$w = new Witness\Silent();
		return array(
			'stl' => new Stop_The_Line($conf, '', 'Gorg', $w, $di),
			'git' => $dig['git'],
		);
	}
}

