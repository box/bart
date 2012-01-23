<?php
$path = dirname(__DIR__) . '/';
require_once $path . 'setup.php';

class Git_Test extends Bart_Base_Test_Case
{
	public function test_chain_commands()
	{
		$git = new Git('.git');
		$hello_world_commands = array(
			'hello world',
			'and then there was more',
		);

		$method = Reflection_Helper::get_method('Git', 'chain_commands');
		$hello = $method->invokeArgs($git, array($hello_world_commands));

		$this->assertEquals($hello,
			'git --git-dir=.git hello world && git --git-dir=.git and then there was more',
			'Chain commands produced wrong cmd');
	}

	public function test_fetch()
	{
		$shell_mock = new Mock_Shell($this);
		$cmd = 'git --git-dir=.git fetch origin';
		$shell_mock->expect_exec($cmd, '', 0, 0);

		$di = new Diesel();
		$di->register_local('Git', 'Shell', function($params) use($shell_mock) {
			return $shell_mock;
		});

		$git = new Git('.git', 'origin', $di);
		$git->fetch();
	}

	public function test_bad_fetch()
	{
		$shell_mock = new Mock_Shell($this);
		$cmd = 'git --git-dir=.git fetch origin';
		$fail_msg = 'Couldn\'t contact origin';
		$shell_mock->expect_exec($cmd, $fail_msg, 1, 0);

		$di = new Diesel();
		$di->register_local('Git', 'Shell', function($params) use($shell_mock) {
			return $shell_mock;
		});

		$git = new Git('.git', 'origin', $di);
		$this->assert_throws('Git_Exception', "Error in fetch: $fail_msg", function() use($git) {
			$git->fetch();
		});
	}

	public function test_get_change_id_bad()
	{
		$shell_mock = $this->getMock('Shell');
		$shell_mock->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color hash')
				->will($this->returnValue('Some random commit message'));

		$di = new Diesel();
		$di->register_local('Git', 'Shell', function($params) use($shell_mock) {
			return $shell_mock;
		});

		$git = new Git('', 'origin', $di);

		$this->assert_throws('Git_Exception', 'No Change-Id in commit message for hash',
			function() use($git) {
				$git->get_change_id('hash');
			});
	}

	public function test_get_change_id_with_change_id()
	{
		$change_id = 'Ic32c1fb78b39ab5463476dab2f929a3e098999c1';
		$commit_msg = "commit 99479139d505a6fa576e5bca710a45ce4bbf4a04
Author: John Braynard <jbraynard@box.com>
Date:   Fri Jan 13 10:37:42 2012 -0800

    Mix new tracks for yo momma

    Change-Id: $change_id";

		$shell_mock = $this->getMock('Shell');
		$shell_mock->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color hash')
				->will($this->returnValue($commit_msg));

		$di = new Diesel();
		$di->register_local('Git', 'Shell', function($params) use($shell_mock) {
			return $shell_mock;
		});

		$git = new Git('', 'origin', $di);
		$actual_change_id = $git->get_change_id('hash');

		$this->assertEquals($change_id, $actual_change_id, 'Should have found change id');
	}

	public function test_get_commit_msg()
	{
		$commit_hash = 'abcde123f';
		$shell_mock = $this->getMock('Shell');
		$shell_mock->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color ' . $commit_hash)
				->will($this->returnValue('Some random commit message'));

		$di = new Diesel();
		$di->register_local('Git', 'Shell', function($params) use($shell_mock) {
			return $shell_mock;
		});

		$git = new Git('', 'origin', $di);
		$msg = $git->get_commit_msg($commit_hash);

		$this->assertEquals('Some random commit message', $msg,
				'Git did not return proper commit message');
	}

}
