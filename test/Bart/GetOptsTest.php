<?php
namespace Bart;

class GetOptsTest extends BaseTestCase
{
	public function testParsing()
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

	public function testDoubleDashEndOfOptions()
	{
		$args = array('name', '--opt1', 'opt1val', 'arg1', '--', 'arg2', '--arg3');

		$opts = GetOpts::parse(array(
			'opt1' => array('switch' => 'opt1', 'type' => GETOPT_VAL),
		), $args);

		$cmdArgs = $opts['cmdline'];
		$this->assertCount(3, $cmdArgs, 'cmdline');
		// --arg3 should be treated as an argument, NOT as an option
		$this->assertEquals(array('arg1', 'arg2', '--arg3'), $cmdArgs, 'cmdline');
	}
}
