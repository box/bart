<?php
namespace Bart\Jenkins;

use Bart\Witness;
use Bart\Diesel;
use Bart\Curl;

class Job_Test extends \Bart\Base_Test_Case
{
	public static $domain = 'www.norris.com';
	public static $job_name = 'chuck norris';

	public function tear_down()
	{
		Curl\Helper::$cache = array();
	}

	/**
	 * Mock the metadata returned by curl
	 */
	public static function mock_metadata($last_success, $last_completed)
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
			'lastSuccessfulBuild' => array('number' => $last_success),
			'lastCompletedBuild' => array('number' => $last_completed),
		);

		Curl\Helper::$cache[$url] = json_encode($norris_metadata, true);
	}

	public function test_is_healthy()
	{
		self::mock_metadata(123, 123);
		$job = new Job(self::$domain, self::$job_name, new Witness\Silent());
		$this->assertTrue($job->is_healthy(), 'Expected that job would be healthy');
	}

	public function test_is_unhealthy()
	{
		self::mock_metadata(123, 122);
		$job = new Job(self::$domain, self::$job_name, new Witness\Silent());
		$this->assertFalse($job->is_healthy(), 'Expected that job would be unhealthy');
	}

	public function test_build_is_disabled()
	{
		$domain = self::$domain;
		$job_name = self::$job_name;
		$url = "http://$domain:8080/job/" . rawurlencode($job_name) . '//api/json';
		Curl\Helper::$cache[$url] = json_encode(array('buildable' => 0), true);

		try
		{
			new Job($domain, $job_name, new Witness\Silent());
			$this->fail('Expected exception on disabled job');
		}
		catch (\Exception $e)
		{
			$this->assertEquals($e->getMessage(), "Project $job_name is disabled");
		}
	}

	public function test_fails_on_missing_param()
	{
		try
		{
			new Job('', '', new Witness\Silent());
			$this->fail('Should fail when missing a daomin');
		}
		catch(\Exception $e)
		{
			$this->assertEquals('Must provide a valid domain', $e->getMessage());
		}

		try
		{
			new Job('domain', '', new Witness\Silent());
			$this->fail('Should fail when missing a job name');
		}
		catch(\Exception $e)
		{
			$this->assertEquals('Must provide a job name', $e->getMessage());
		}
	}
}

