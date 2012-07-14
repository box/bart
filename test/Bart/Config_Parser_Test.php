<?php
namespace Bart;

class Config_Parser_Test extends \Bart\BaseTestCase
{
	public function test_default_envs()
	{
		// With no environments
		$parser = new Config_Parser();
		$conf = $parser->parse_conf_file(BART_DIR . 'test/etc/conf-parser.conf');

		$this->assertEquals('black', $conf['favorites']['color'],
				'Expected favorite color to be black');
		$this->assertEquals('45 mph', $conf['speeds']['ford'],
				'Expected speeds to match');
		$this->assertArrayKeyNotExists('audi', $conf['speeds'],
				'Audi should not have a default value for speeds');
	}

	public function test_desert()
	{
		$parser = new Config_Parser(array('desert'));
		$conf = $parser->parse_conf_file(BART_DIR . 'test/etc/conf-parser.conf');

		$this->assertEquals('45 mph', $conf['speeds']['ford'],
				"Expected speeds to match");
		$this->assertEquals('90 mph', $conf['speeds']['audi'],
				"Expected speeds to match");
	}

	public function test_king_matthewa()
	{
		$parser = new Config_Parser(array('king_matthewa'));
		$conf = $parser->parse_conf_file(BART_DIR . 'test/etc/conf-parser.conf');

		$this->assertEquals('45 mph', $conf['speeds']['ford'],
				"Expected speeds to match");
		$this->assertEquals('Quail', $conf['favorites']['wild_game'],
				'Expected matt to like quail');
	}

	public function test_several_envs()
	{
		// Ryan is the most important
		$parser = new Config_Parser(array('king_matthewa', 'jbraynard', 'rluecke'));
		$conf = $parser->parse_conf_file(BART_DIR . 'test/etc/conf-parser.conf');

		// Matt's preference here should not be ignored
		$this->assertEquals('Quail', $conf['favorites']['wild_game'],
				'Expected matt to like quail');
		$this->assertEquals('cyan', $conf['favorites']['color'],
				'Expected Ryan\'s favorite to take precedence');
	}

	public function test_with_dashes_in_env_name()
	{
		// Does ini handle the dashes OK?
		$parser = new Config_Parser(array('van-wright'));
		$conf = $parser->parse_conf_file(BART_DIR . 'test/etc/conf-parser.conf');

		$this->assertEquals('pastel', $conf['favorites']['color'],
				'Frank Lloyd likes pastels');
	}
}
