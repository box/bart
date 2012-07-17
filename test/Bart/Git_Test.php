<?php
namespace Bart;

class Git_Test extends \Bart\BaseTestCase
{
	public function test_chain_commands()
	{
		$this->registerMockShell();
		$git = new Git('.git');
		$hello_world_commands = array(
			'hello world',
			'and then there was more',
		);

		$method = Util\Reflection_Helper::get_method('Bart\\Git', 'chain_commands');
		$hello = $method->invokeArgs($git, array($hello_world_commands));

		$this->assertEquals($hello,
			'git --git-dir=.git hello world && git --git-dir=.git and then there was more',
			'Chain commands produced wrong cmd');
	}

	/**
	 * Register a mock shell instance with Diesel
	 */
	protected function registerMockShell($mockShell = null)
	{
		if (!$mockShell) {
			$mockShell = $this->getMock('Bart\Shell');
		}

		Diesel::registerInstantiator('Bart\Shell', $mockShell, true);
	}

	public function test_fetch()
	{
		$mock_shell = new Stub\Mock_Shell($this);
		$cmd = 'git --git-dir=.git fetch origin';
		$mock_shell->expect_exec($cmd, '', 0, 0);

		$this->registerMockShell($mock_shell);

		$git = new Git('.git', 'origin');
		$git->fetch();
	}

	public function test_bad_fetch()
	{
		$mock_shell = new Stub\Mock_Shell($this);
		$cmd = 'git --git-dir=.git fetch origin';
		$fail_msg = 'Couldn\'t contact origin';
		$mock_shell->expect_exec($cmd, $fail_msg, 1, 0);

		$this->registerMockShell($mock_shell);

		$git = new Git('.git', 'origin');
		$this->assertThrows('Bart\\Git_Exception', "Error in fetch: $fail_msg", function() use($git) {
			$git->fetch();
		});
	}

	public function test_get_change_id_bad()
	{
		$mock_shell = $this->getMock('\\Bart\\Shell');
		$mock_shell->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color hash')
				->will($this->returnValue('Some random commit message'));

		$this->registerMockShell($mock_shell);

		$git = new Git('', 'origin');

		$this->assertThrows('Bart\\Git_Exception', 'No Change-Id in commit message for hash',
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

		$mock_shell = $this->getMock('\\Bart\\Shell');
		$mock_shell->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color hash')
				->will($this->returnValue($commit_msg));

		$this->registerMockShell($mock_shell);

		$git = new Git('', 'origin');
		$actual_change_id = $git->get_change_id('hash');

		$this->assertEquals($change_id, $actual_change_id, 'Should have found change id');
	}

	public function test_get_commit_msg()
	{
		$commit_hash = 'abcde123f';
		$mock_shell = $this->getMock('\\Bart\\Shell');
		$mock_shell->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color ' . $commit_hash)
				->will($this->returnValue('Some random commit message'));

		$this->registerMockShell($mock_shell);

		$git = new Git('', 'origin');
		$msg = $git->get_commit_msg($commit_hash);

		$this->assertEquals('Some random commit message', $msg,
				'Git did not return proper commit message');
	}
}
