<?php
$path = dirname(dirname(__DIR__)) . '/';
require_once $path . 'setup.php';

class Gerrit_Api_Test extends Bart_Base_Test_Case
{
	public $change_id = 'Iabcde123f';
	public $commit_hash = 'abcde123fg';

	public function test_bad_exit_ssh_status()
	{
		$json = 'Gerrit is sleeping, come again later.';
		$g = $this->configure_gerrit_api(255, $json);

		$me = $this;
		$msg = 'Gerrit API Exception: ' . $json;
		$this->assert_throws('Exception', $msg, function() use($g, $me) {
			$g->get_approved_change($me->change_id, $me->commit_hash);
		});

	}

	public function test_empty_results()
	{
		$json = array('{"type":"stats","rowCount":0,"runTimeMilliseconds":16}');
		$g = $this->configure_gerrit_api(0, $json);
		$change_data = $g->get_approved_change($this->change_id, $this->commit_hash);

		$this->assertNull($change_data, 'Empty record set should return null');
	}

	public function test_more_than_one_record_matches()
	{
		// Three results from gerrit
		$json = array('{}', '{}', '{}');
		$g = $this->configure_gerrit_api(0, $json);

		$me = $this;
		$msg = 'More than one gerrit record matched';
		$this->assert_throws('Exception', $msg, function() use($g, $me) {
			$g->get_approved_change($me->change_id, $me->commit_hash);
		});

	}

	public function test_legit_gerrit_reponse()
	{
		$json = array(
			'{"project":"scm","branch":"v5-dev","topic":"remove_collab",'
				. '"id":"Iabcde123f","number":"654321",'
				. '"subject":"BOX-123 The internet is slow",'
				. '"owner":{"name":"Atul Bhatia",'
				. '"email":"abhatia@box.com"},"url":"http:/gerrit:8080/654321",'
				. '"lastUpdated":1326935062,"sortKey":"001a7a6000005f95","open":false,'
				. '"status":"MERGED"}',
			'{"type":"stats","rowCount":1,"runTimeMilliseconds":16}',
		);

		$g = $this->configure_gerrit_api(0, $json);
		$change = $g->get_approved_change($this->change_id, $this->commit_hash);

		$this->assertEquals('MERGED', $change['status'],
				'Gerrit change status not parsed correclty');
	}

	private function configure_gerrit_api($status, $json)
	{
		$change_id = $this->change_id;
		$commit_hash = $this->commit_hash;

		$remote_gerrit_cmd = 'gerrit query --format=JSON ' . $change_id
				. " commit:$commit_hash label:CodeReview=10";

		$ssh_result = array(
			'exit_status' => $status,
			'output' => $json,
		);
		$gerrit_conf = array('host' => '', 'port' => '');

		$ssh_mock = $this->getMock('Ssh', array(), array(), '', false);
		$ssh_mock->expects($this->once())
				->method('execute')
				->with($this->equalTo($remote_gerrit_cmd))
				->will($this->returnValue($ssh_result));
		$di = new Diesel();
		$di->register_local('Gerrit_Api', 'Ssh', function() use($ssh_mock) {
			return $ssh_mock;
		});

		return new Gerrit_Api($gerrit_conf, new Witness_Silent(), $di);
	}
}
