<?php
$path = dirname(dirname(__DIR__)) . '/';
require_once $path . 'setup.php';

class Gerrit_Approved_Test extends Bart_Base_Test_Case
{
	private static $conf = array('host' => 'gorgoroth.com', 'port' => '42');
	private $w;

	public function set_up()
	{
		$this->w = new Witness_Silent();
	}

	public function test_valid_commit()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mock_g = $this->getMock('Gerrit_Api', array(), array(), '', false);
		$mock_g->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
					// Just some non-null value
			->will($this->returnValue(array('id' => $change_id)));

		$di = $this->configure_for($change_id, $commit_hash, $mock_g);

		$hook = new Git_Hook_Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w, $di);
		$hook->verify($commit_hash);
	}

	public function test_change_not_found()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mock_g = $this->getMock('Gerrit_Api', array(), array(), '', false);
		$mock_g->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
					// Just some non-null value
			->will($this->returnValue(null));

		$di = $this->configure_for($change_id, $commit_hash, $mock_g);

		$hook = new Git_Hook_Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w, $di);

		$msg = 'An approved review was not found in Gerrit for commit '
		. $commit_hash . ' with Change-Id ' . $change_id;
		$this->assert_throws('Exception', $msg, function() use($hook, $commit_hash){
			$hook->verify($commit_hash);
		});
	}

	public function test_exception_in_gerrit()
	{
		$change_id = 'Iabcde123';
		$commit_hash = 'abcde123';

		$mock_g = $this->getMock('Gerrit_Api', array(), array(), '', false);
		$mock_g->expects($this->once())
			->method('get_approved_change')
			->with($this->equalTo($change_id), $this->equalTo($commit_hash))
					// Just some non-null value
			->will($this->throwException(new Exception()));

		$di = $this->configure_for($change_id, $commit_hash, $mock_g);

		$hook = new Git_Hook_Gerrit_Approved(self::$conf, '.git', 'grinder', $this->w, $di);

		$msg = 'Error getting Gerrit review info';
		$this->assert_throws('Exception', $msg, function() use($hook, $commit_hash){
			$hook->verify($commit_hash);
		});
	}

	private function configure_for($change_id, $commit_hash, $mock_g)
	{
		$phpu = $this;
		$conf = self::$conf;
		$dig = Git_Hook_Base_Test::get_diesel($this, 'Git_Hook_Gerrit_Approved');
		$di = $dig['di'];
		$di->register_local('Git_Hook_Gerrit_Approved', 'Gerrit_Api',
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
