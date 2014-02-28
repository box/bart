<?php
namespace Bart;

use Bart\Shell\Command;

class GitTest extends \Bart\BaseTestCase
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
		if (!$mockShell)
		{
			$mockShell = $this->getMock('Bart\Shell');
		}

		Diesel::registerInstantiator('Bart\Shell', $mockShell, true);
	}

	public function test_fetch()
	{
		$mock_shell = new Stub\MockShell($this);
		$cmd = 'git --git-dir=.git fetch origin';
		$mock_shell->expectExec($cmd, array(''), 0, 0);

		$this->registerMockShell($mock_shell);

		$git = new Git('.git', 'origin');
		$git->fetch();
	}

	public function test_bad_fetch()
	{
		$mockShell = new Stub\MockShell($this);
		$cmd = 'git --git-dir=.git fetch origin';
		$failMsg = 'Couldn\'t contact origin';
		$mockShell->expectExec($cmd, array($failMsg), 1, 0);

		$this->registerMockShell($mockShell);

		$git = new Git('.git', 'origin');
		$this->assertThrows('Bart\\Git_Exception', 'Error in fetch: ' . print_r(array($failMsg), true),
			function() use($git)
			{
				$git->fetch();
			});
	}

	public function test_get_change_id_bad()
	{
		$mockShell = $this->getMock('\\Bart\\Shell');
		$mockShell->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color hash')
				->will($this->returnValue('Some random commit message'));

		$this->registerMockShell($mockShell);

		$git = new Git('', 'origin');

		$this->assertThrows('Bart\\Git_Exception', 'No Change-Id in commit message for hash',
			function() use($git)
			{
				$git->get_change_id('hash');
			});
	}

	public function test_get_change_id_with_change_id()
	{
		$changeId = 'Ic32c1fb78b39ab5463476dab2f929a3e098999c1';
		$commitMsg = "commit 99479139d505a6fa576e5bca710a45ce4bbf4a04
Author: John Braynard <jbraynard@box.com>
Date:   Fri Jan 13 10:37:42 2012 -0800

    Mix new tracks for yo momma

    Change-Id: $changeId";

		$mockShell = $this->getMock('\\Bart\\Shell');
		$mockShell->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color hash')
				->will($this->returnValue($commitMsg));

		$this->registerMockShell($mockShell);

		$git = new Git('', 'origin');
		$actualChangeId = $git->get_change_id('hash');

		$this->assertEquals($changeId, $actualChangeId, 'Should have found change id');
	}

	public function test_get_commit_msg()
	{
		$commitHash = 'abcde123f';
		$mockShell = $this->getMock('\\Bart\\Shell');
		$mockShell->expects($this->once())
				->method('shell_exec')
				->with('git --git-dir= show -s --no-color ' . $commitHash)
				->will($this->returnValue('Some random commit message'));

		$this->registerMockShell($mockShell);

		$git = new Git('', 'origin');
		$msg = $git->get_commit_msg($commitHash);

		$this->assertEquals('Some random commit message', $msg,
			'Git did not return proper commit message');
	}

	public function testGetRevList()
	{
		$command = $this->getMock('\Bart\Shell\Command', array(), array(), '', false);
		$command->expects($this->once())
			->method('run')
			->will($this->returnValue(['abcdef123']));

		$shell = $this->getMock('\Bart\Shell');
		$shell->expects($this->once())
			->method('command')
			->with('git --git-dir= rev-list %s..%s', 'HEAD^', 'HEAD')
			->will($this->returnValue($command));

		$this->registerMockShell($shell);

		$git = new Git('', 'origin');

		$this->assertEquals(['abcdef123'], $git->getRevList('HEAD^', 'HEAD'), 'rev list');
	}

	public function testGetRevListCount()
	{
		$command = $this->getMock('\Bart\Shell\Command', array(), array(), '', false);
		$command->expects($this->once())
			->method('run')
			->will($this->returnValue(array(1,2,3,4,5,6,7,8,9)));

		$shell = $this->getMock('\Bart\Shell');
		$shell->expects($this->once())
			->method('command')
			->will($this->returnValue($command));

		$this->registerMockShell($shell);

		$git = new Git('', 'origin');

		$this->assertEquals(9, $git->getRevListCount(), 'rev list count');
	}
}
