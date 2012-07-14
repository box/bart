<?php
namespace Bart\Git_Hook;

class Gerrit_Approved_Test extends \Bart\BaseTestCase
{
	private static $conf = array('gerrit' =>
		array('host' => 'gorgoroth.com', 'port' => '42')
	);
	private $w;

	public function setUp()
	{
		$this->w = new \Bart\Witness\Silent();
		parent::setUp();
	}

	public function test_valid_commit()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mock_g = $this->getMock('\\Bart\\Gerrit\\Api', array(), array(), '', false);
		$mock_g->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
					// Just some non-null value
			->will($this->returnValue(array('id' => $change_id)));

		$di = $this->configure_for($change_id, $commit_hash, $mock_g);

		$hook = new Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w, $di);
		$hook->verify($commit_hash);
	}

	public function test_change_not_found()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mock_g = $this->getMock('\\Bart\\Gerrit\\Api', array(), array(), '', false);
		$mock_g->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
			->will($this->returnValue(null));

		$di = $this->configure_for($change_id, $commit_hash, $mock_g);

		$hook = new Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w, $di);

		$msg = 'An approved review was not found in Gerrit for commit '
		. $commit_hash . ' with Change-Id ' . $change_id;
		$this->assertThrows('\Exception', $msg, function() use($hook, $commit_hash){
			$hook->verify($commit_hash);
		});
	}

	public function test_exception_in_gerrit()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mock_g = $this->getMock('\\Bart\\Gerrit\\Api', array(), array(), '', false);
		$mock_g->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
			->will($this->throwException(new \Exception()));

		$di = $this->configure_for($change_id, $commit_hash, $mock_g);

		$hook = new Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w, $di);

		$msg = 'Error getting Gerrit review info';
		$this->assertThrows('\Exception', $msg, function() use($hook, $commit_hash){
			$hook->verify($commit_hash);
		});
	}

	private function configure_for($change_id, $commit_hash, $mock_g)
	{
		$phpu = $this;
		$conf = self::$conf['gerrit'];
		$dig = Base_Test::get_diesel($this, 'Bart\\Git_Hook\\Gerrit_Approved');
		$di = $dig['di'];
		$di->register_local('Bart\\Git_Hook\\Gerrit_Approved', '\\Bart\\Gerrit\\Api',
			function($params) use($phpu, $conf, $mock_g) {
				$phpu->assertEquals($conf, $params['gerrit_conf'],
						'Expected params to contain gerrit conf');

				return $mock_g;
			});

		$mock_git = $dig['git'];
		$mock_git->expects($this->once())
			->method('get_change_id')
			->with($this->equalTo($commit_hash))
			->will($this->returnValue($change_id));

		return $di;
	}
}
