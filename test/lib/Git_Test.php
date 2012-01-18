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
		$this->assert_error('Git_Exception', "Error in fetch: $fail_msg", function() use($git) {
			$git->fetch();
		});
	}
}
