<?php
namespace Bart\Jenkins;

use Bart\BaseTestCase;

class Job_Test extends BaseTestCase
{
	public static $projectPath = 'job/chuck norris';
	public function testIsHealthy()
	{
		$conn = $this->configureForHealthTests(123, 123);
		$job = new Job($conn, self::$projectPath);
		$this->assertTrue($job->is_healthy(), 'Expected that job would be healthy');
	}

	public function testIsUnhealthy()
	{
		$conn = $this->configureForHealthTests(123, 122);
		$job = new Job($conn, self::$projectPath);
		$this->assertFalse($job->is_healthy(), 'Expected that job would be unhealthy');
	}

	public function testBuildIsDisabled()
	{
        $conn = $this->createMockConnection();
        $this->setExpectedException('\InvalidArgumentException');
        new Job($conn, self::$projectPath);

	}

	/**
	 * Create a mock Jenkins connection
	 * @param array $returnContent The data that Jenkins should return (as an array)
	 * @return Connection A mock Jenkins Connection
	 */
	private function createMockConnection(array $returnContent = [])
	{
		/** @var \Bart\Jenkins\Connection $conn */
		return $this->shmock('\Bart\Jenkins\Connection', function ($stub) use ($returnContent)
			/** @var \Bart\Jenkins\Connection $stub */ {
			$stub->curlJenkinsApi()->once()->return_value($returnContent);
		}, true);
	}

	/**
	 * Mock the metadata returned by curl
	 * @param $lastSuccess
	 * @param $lastCompleted
	 * @return Connection A mock Jenkins Connection
	 */
	private function configureForHealthTests($lastSuccess, $lastCompleted)
	{
		// The essential metadata the Job class needs to instantiate
		$norrisMetadata = array(
			'buildable' => 1,
			'property' => array(
				'0' => array(
					'parameterDefinitions' => array(),
				),
			),
			'lastSuccessfulBuild' => array('number' => $lastSuccess),
			'lastCompletedBuild' => array('number' => $lastCompleted),
		);

		return $this->createMockConnection($norrisMetadata);
	}
}

