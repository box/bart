<?php
namespace Bart;

class SshTest extends \Bart\BaseTestCase
{
	public function test_construct_missing_server()
	{
		$this->assertThrows('\Exception', 'Invalid server ', function() {
			new Ssh(null);
		});
	}

	public function test_get_current_user()
	{
		$server = 'server';
		$cmd = 'whoami';
		$confStub = array(
			'connection' => array(
				'user' => 'jbraynard',
				'key_file_location' => 'path/to/private_key',
				),
			);

		// =( ...until there's a better way to match commands to results
		$sshCmd = "ssh -q -p 22  -i {$confStub['connection']['key_file_location']}"
				. "   -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"
				. " {$confStub['connection']['user']}@$server $cmd 2>&1";

		$mockShell = new Stub\MockShell($this);
		$mockShell->expectExec($sshCmd, array('Anything'), 0, 0);

		$mockConfig = $this->getMock('\\Bart\\Config_Parser', array(), array(), '', false);
		$mockConfig->expects($this->once())
				->method('parse_conf_file')
				->will($this->returnValue($confStub));

		Diesel::registerInstantiator('Bart\Shell', function() use($mockShell) {
			return $mockShell;
		});
		Diesel::registerInstantiator('Bart\Config_Parser', function() use($mockConfig) {
			return $mockConfig;
		});

		$ssh = new Ssh($server);
		$ssh->use_auto_user();
		$result = $ssh->execute($cmd);

		$this->assertEquals(0, $result['exit_status'], 'Wrong exit_status');
		$this->assertEquals(array('Anything'), $result['output'], 'Wrong output');
	}
}
