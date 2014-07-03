<?php
namespace Bart;

class SshWrapperTest extends BaseTestCase
{
	public function testWithAllOptions()
	{
		$host = 'example.com';
		$user = 'lucky';
		$keyFile = 'path/to/keyfile';
		$remoteCommand = "remote command with 'single quoted string'";

		$cmd = $this->getMock('Bart\Shell\Command', array(), array(), '', false);
		$cmd->expects($this->once())
			->method('run')
			->will($this->returnValue(array('Some string')));

		$shell = $this->getMock('Bart\Shell');
		$shell->expects($this->once())
			->method('command')
			->with(
				'ssh %s -q -p %s -o %s -o %s -l %s -i %s %s',
				$host,
				22,
				'UserKnownHostsFile=/dev/null',
				'StrictHostKeyChecking=no',
				$user,
				$keyFile,
				$remoteCommand
			)->will($this->returnValue($cmd));
		Diesel::registerInstantiator('Bart\Shell', function() use ($shell)
		{
			return $shell;
		});

		$ssh = new SshWrapper($host);
		$ssh->setCredentials($user, $keyFile);

		$output = $ssh->exec($remoteCommand);

		$this->assertCount(1, $output);
		$this->assertEquals('Some string', $output[0]);
	}

	public function testGetNewCommand()
	{
		Diesel::registerInstantiator('Bart\Shell', function() {
			// Just let Shell do its magic
			return new Shell();
		});

		$host = 'www.example.com';
		$ssh = new SshWrapper($host);
		$command = $ssh->createShellCommand('who -r');

		$expectedSshCommand = "ssh 'www.example.com' -q -p '22' -o 'UserKnownHostsFile=/dev/null' -o 'StrictHostKeyChecking=no' 'who -r'";
		$this->assertEquals($expectedSshCommand, "{$command}", 'the command string');
	}
}
