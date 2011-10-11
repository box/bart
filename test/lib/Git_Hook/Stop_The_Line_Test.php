<?php
$path = dirname(dirname(__DIR__)) . '/';
require_once $path . 'setup.php';

class Git_Hook_Stop_The_Line_Test extends Bart_Base_Test_Case
{
	private $stl;

	public function set_up()
	{
		Jenkins_Job_Test::mock_metadata(321, 123);
		$this->stl = new Git_Hook_Stop_The_Line(
			Jenkins_Job_Test::$domain,
			Jenkins_Job_Test::$job_name,
			new Witness_Silent());
	}

	public function tear_down()
	{
		Curl_Helper::$cache = array();
	}

	public function test_commit_msg_contains_buildfix()
	{
		$msg = 'some message {buildfix}';
		$verified = $this->stl->verify($msg);

		$this->assertTrue($verified, 'Build message contains buildfix');
	}

	public function test_commit_msg_does_not_contain_buildfix()
	{
		$msg = 'some message';
		$verified = $this->stl->verify($msg);

		$this->assertFalse($verified, 'Build should be rejected');
	}

	public function test_multi_line_commit_msg_contains_buildfix()
	{
		$msg = 'some message
			some more messages

			and then again, a few others

			{buildfix}

			It happened in Monterey';
		$verified = $this->stl->verify($msg);

		$this->assertTrue($verified, 'Build message contains buildfix');
	}

	public function test_healthy_build_passes()
	{
		Jenkins_Job_Test::mock_metadata(100, 100);
		$happy_stl = new Git_Hook_Stop_The_Line(
			Jenkins_Job_Test::$domain,
			Jenkins_Job_Test::$job_name,
			new Witness_Silent());

		$verified = $happy_stl->verify('');
		$this->assertTrue($verified, 'Happy build won\'t need to check the commit message');
	}
}

