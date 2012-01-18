<?php
$path = dirname(__DIR__) . '/';
require_once $path . 'setup.php';

class GetOpts_Test extends Bart_Base_Test_Case
{
	public function test_parsing()
	{
		$args = array('program-name', '--help', '--version');

		$opts = GetOpts::parse(array(
			  'help' => array('switch' => 'help', 'type' => GETOPT_SWITCH),
			  'version' => array('switch' => 'version', 'type' => GETOPT_SWITCH),
			  'unused' => array('switch' => 'unused', 'type' => GETOPT_SWITCH),
			),
			$args);

		$this->assertEquals(1, $opts['help'], 'Help flag not parsed');
		$this->assertEquals(1, $opts['version'], 'Version flag not parsed');
		$this->assertEquals(0, $opts['unused'], 'Unused flag was improperly set');
		$this->assertArrayKeyNotExists('bogus', $opts, 'Bogus key was in array!');
	}
}
