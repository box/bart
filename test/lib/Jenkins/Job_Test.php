<?php
$path = dirname(dirname(__DIR__)) . '/';
require_once $path . 'setup.php';

class Jenkins_Job_Test extends Bart_Base_Test_Case
{
	public static $domain = 'www.norris.com';
	public static $job_name = 'chuck norris';

	public function tear_down()
	{
		Curl_Helper::$cache = array();
	}

	/**
	 * Mock the metadata returned by curl
	 */
	public static function mock_metadata($health_score)
	{
		$domain = self::$domain;
		$url = "http://$domain:8080/job/" . rawurlencode(self::$job_name) . '//api/json';

		// The essential metadata the Job class needs to instantiate
		$norris_metadata = array(
			'buildable' => 1,
			'property' => array(
				'0' => array(
					'parameterDefinitions' => array(),
				),
			),
			'healthReport' => array(
				'0' => array(), // extra element to ensure verifying against the last one
				'1' => array(
					'score' => $health_score,
				),
			),
		);

		Curl_Helper::$cache[$url] = json_encode($norris_metadata, true);
	}

	public function test_is_healthy()
	{
		self::mock_metadata('100');
		$job = new Jenkins_Job(self::$domain, self::$job_name, new Witness_Silent());
		$this->assertTrue($job->is_healthy(), 'Expected that job would be healthy');
	}

	public function test_is_unhealthy()
	{
		self::mock_metadata('99');
		$job = new Jenkins_Job(self::$domain, self::$job_name, new Witness_Silent());
		$this->assertFalse($job->is_healthy(), 'Expected that job would be unhealthy');
	}
	
	public function test_fails_on_missing_param()
	{
		try
		{
			new Jenkins_Job('', '', new Witness_Silent());
			$this->fail('Should fail when missing a daomin');
		}
		catch(Exception $e)
		{
			$this->assertEquals('Must provide a valid domain', $e->getMessage());
		}

		try
		{
			new Jenkins_Job('domain', '', new Witness_Silent());
			$this->fail('Should fail when missing a job name');
		}
		catch(Exception $e)
		{
			$this->assertEquals('Must provide a job name', $e->getMessage());
		}
	}
}

