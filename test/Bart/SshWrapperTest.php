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
}
