<?php
namespace Bart\Jenkins;

use Bart\Diesel;
use Bart\Curl;

class Job_Test extends \Bart\BaseTestCase
{
	public static $domain = 'www.norris.com';
	public static $job_name = 'chuck norris';

	/**
	 * Mock the metadata returned by curl
	 */
	public function configure_for_health_tests($last_success, $last_completed)
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

		$json = json_encode($norris_metadata, true);
		$this->configure_diesel($url, $json);
	}

	/**
	 * Configure Job for injection of stub Curl with $json
	 * @param string $url Expected Jenkins URL
	 * @param JSON $json
	 */
	private function configure_diesel($url, $json)
	{
		$mock_curl = $this->getMock('\\Bart\\Curl', array(), array(), '', false);
		$mock_curl->expects($this->once())
			->method('get')
			->with($this->equalTo(''), $this->equalTo(array()))
		    ->will($this->returnValue(array('content' => $json)));

		$phpu = $this;
		Diesel::registerInstantiator('Bart\Curl',
			function($urlParam, $portParam) use($phpu, $url, $mock_curl) {
				$phpu->assertEquals($url, $urlParam, 'url');
				$phpu->assertEquals(8080, $portParam, 'port');
				return $mock_curl;
			});
	}

	public function test_is_healthy()
	{
		$this->configure_for_health_tests(123, 123);
		$job = new Job(self::$domain, self::$job_name);
		$this->assertTrue($job->is_healthy(), 'Expected that job would be healthy');
	}

	public function test_is_unhealthy()
	{
		$this->configure_for_health_tests(123, 122);
		$job = new Job(self::$domain, self::$job_name);
		$this->assertFalse($job->is_healthy(), 'Expected that job would be unhealthy');
	}

	public function test_build_is_disabled()
	{
		$domain = self::$domain;
		$job_name = self::$job_name;
		$url = "http://$domain:8080/job/" . rawurlencode($job_name) . '//api/json';
		$this->configure_diesel($url, json_encode(array('buildable' => 0), true));

		try
		{
			new Job($domain, $job_name);
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
			new Job('', '');
			$this->fail('Should fail when missing a daomin');
		}
		catch(\Exception $e)
		{
			$this->assertEquals('Must provide a valid domain', $e->getMessage());
		}

		try
		{
			new Job('domain', '');
			$this->fail('Should fail when missing a job name');
		}
		catch(\Exception $e)
		{
			$this->assertEquals('Must provide a job name', $e->getMessage());
		}
	}
}

