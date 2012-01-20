<?php
$path = dirname(__DIR__) . '/';
require_once $path . 'setup.php';

class Ssh_Test extends Bart_Base_Test_Case
{
	public function test_construct_missing_server()
	{
		$this->assert_error('Exception', 'Invalid server ', function() {
			$g = new Ssh(null);
		});
	}

	public function test_get_current_user()
	{
		$server = 'server';
		$cmd = 'whoami';
		$conf_stub = array(
			'connection' => array(
				'user' => 'jbraynard',
				'key_file_location' => 'path/to/private_key',
				),
			);

		// =( ...until there's a better way to match commands to results
		$ssh_cmd = "ssh -q -p 22  -i {$conf_stub['connection']['key_file_location']}"
				. "   -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no"
				. " {$conf_stub['connection']['user']}@$server $cmd 2>&1";

		$mock_shell = new Mock_Shell($this);
		$mock_shell->expect_exec($ssh_cmd, 'Anything', 0, 0);

		$mock_config = $this->getMock('Config_Parser', array(), array(), '', false);
		$mock_config->expects($this->once())
				->method('parse_conf_file')
				->will($this->returnValue($conf_stub));

		$di = new Diesel();
		$di->register_local('Ssh', 'Shell', function() use($mock_shell){
			return $mock_shell;
		});
		$di->register_local('Ssh', 'Config_Parser', function() use($mock_config){
			return $mock_config;
		});

		$ssh = new Ssh($server, $di);
		$ssh->use_auto_user();
		$result = $ssh->execute($cmd);

		$this->assertEquals(0, $result['exit_status'], 'Wrong exit_status');
		$this->assertEquals('Anything', $result['output'], 'Wrong output');
	}
}
