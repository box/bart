<?php
namespace Bart\Jenkins;

use Bart\Log4PHP;

/**
 * Interface to Jenkins jobs
 */
class Job
{
	private $defaultParams = [];
	private $myBuildData;
	private $metadata;
	/** @var \Logger */
	private $logger;

	/** @var Connection $connection */
	private $connection;
	/** @var string $baseApiPath */
	private $baseApiPath;

	/**
	 * Job constructor. Loads metadata about a project.
	 * @param Connection $connection
	 * @param array $projects This parameter specifies the location of the Jenkins Job.
	 * For example, if your Job is defined in the following third level project: Base->Build->Example,
	 * the array you must pass in is ['Base', 'Build', 'Example']. The order of the array is
	 * maintained when loading the metadata, and hence it's critical.
     */
	public function __construct(Connection $connection, array $projects)
	{
		$this->connection = $connection;
		if (count($projects) === 0) {
			throw new \InvalidArgumentException('You must pass in a non-empty projects array.');
		}
		$this->logger = Log4PHP::getLogger(__CLASS__);

		$this->baseApiPath = '/';
		for ($i = 0; $i < count($projects); $i++) {
			$projectEncoded = rawurlencode($projects[$i]);
			$this->baseApiPath .= "job/{$projectEncoded}/";
		}
		$this->metadata = $this->getJson(array());

		if (!isset($this->metadata['buildable'])) {
			throw new \InvalidArgumentException("The project at path '{$this->baseApiPath}'' is disabled");
		}
		$this->setDefaultParameters();
	}

	/**
	 * Load the default set of parameters defined by project
	 */
	private function setDefaultParameters()
	{
		$properties = $this->metadata['property'];
		if (count($properties) == 0) return;

		// To determine the default parameters, we need to figure out which of sub-arrays
		// actually contains the 'parameterDefinitions'.
		$params = null;
		for ($i = 0; $i < count($properties); $i++) {
			if (isset($properties[$i]['parameterDefinitions'])) {
				$params = (array) $properties[$i]['parameterDefinitions'];
				break;
			}
		}

		if ($params === null) {
			throw new \InvalidArgumentException("The parameterDefinitions are not part of the data.");
		}

		foreach ($params as $p => $param)
		{
			$default = $param['defaultParameterValue'];
			$this->defaultParams[$default['name']] = $default['value'];
		}
	}

	/**
	 * @returns true if the last build was successful
	 */
	public function is_healthy()
	{
		// TODO default if property is not defined?
		$lastSuccess = $this->metadata['lastSuccessfulBuild']['number'];
		$lastCompleted = $this->metadata['lastCompletedBuild']['number'];

		// Another alternative is lastBuild.result == 'SUCCESS'
		return $lastSuccess === $lastCompleted;
	}

	/**
	 * Enqueue a build with Jenkins
	 *
	 * @param array $build_params Any param values to override the project defaults
	 * @throws \Exception
	 */
	public function start(array $build_params = [])
	{
		$last_completed_build_id = $this->last_build_id(true);
		$this->logger->debug('Last completed build: ' . $last_completed_build_id);

		$params_json = $this->build_params_json($build_params);
		$this->postJson(
			array('build'),
			array(
				'json' => $params_json,
				'delay' => '0sec',
			));

		// This gives back the general information about job
		$metadata = $this->getJson(array());

		if ($metadata['inQueue'] > 0)
		{
			// Build is queued, but must wait and hasn't been assigned a number
			$this->myBuildData = $metadata['nextBuildNumber'];
			$this->logger->debug('Queued build: ' . $this->myBuildData);

			// @TODO Sleep until build should be running?
			// sleep($metadata['queueItem']['buildableStartMilliseconds']);
		}
		else
		{
			// If no builds blocking (system wide), this build starts right away
			// ...and has been assigned a build number
			$this->myBuildData = $this->last_build_id(false);
			$this->logger->debug('Started build: ' . $this->myBuildData);
		}

		if ($last_completed_build_id == $this->myBuildData)
		{
			throw new \Exception('Could not create new jenkins job. Quitting.');
		}

		return $this->myBuildData;
	}

	/**
	 * Build number of the last build
	 * @param $completed - If true, get last *completed* build, otherwise last build
	 */
	private function last_build_id($completed = false)
	{
		$build_type = $completed ? 'lastCompletedBuild' : 'lastBuild';

		$last_build_data = $this->getJson(array($build_type));

		return $last_build_data['number'];
	}

	/**
	 * @return string Success, Failure, or Incomplete
	 */
	public function query_status()
	{
		$job_data = $this->getJson(array("{$this->myBuildData}"));

		return $job_data['result'];
	}

	/**
	 * Keep polling jenkins every $poll_period seconds until build is complete
	 * @param int $poll_period
	 * @param int $timeout_after Maximum total minutes to poll before giving up
	 */
	public function wait_until_complete($poll_period = 1, $timeout_after = 45)
	{
		// Poll jenkins until last build number is our build number or greater
		$last_completed_build_id = $this->last_build_id(true);
		$started = time();
		while ($last_completed_build_id < $this->myBuildData
			&& time() < (60 * $timeout_after) + $started)
		{
			sleep($poll_period);

			// Consider persisting curl handle between these requests
			// ...if we see perf. degredation
			$last_completed_build_id = $this->last_build_id(true);
		}
	}

	/**
	 * Curl Jenkins API for details about job
	 * @param $resourceItems - List of strings defining path to job resource
	 *
	 * E.g. get details of Acceptance Test job 36 with getJson(array('36'))
	 *      ==> http://qa-hudson1.dev/job/Acceptance%20Test/36/api/json
	 */
	private function getJson(array $resourceItems)
	{
		return $this->connection->curlJenkinsApi($this->buildApiPath($resourceItems), null);
	}

	/**
	 * @see getJson but with POST data
	 * @param array $resourceItems
	 * @param array $httpPostArray
	 */
	private function postJson(array $resourceItems, array $httpPostArray)
	{
		return $this->connection->curlJenkinsApi($this->buildApiPath($resourceItems), $httpPostArray);
	}

	/**
	 * The JSON representation of the build parameters expected by the job
	 * coalescing defaults and @param $override values
	 *
	 * @param array $override Override the default values defined by project
	 */
	private function build_params_json(array $override)
	{
		$params = array();
		foreach ($this->defaultParams as $name => $value)
		{
			$params[] = array(
				'name' => $name,
				'value' => isset($override[$name]) ?  $override[$name] : $value,
			);
		}

		$jenkins_params = array('parameter' => $params);

		return json_encode($jenkins_params);
	}

	private function buildApiPath(array $resourceItems)
	{
		$resourcePath = '';
        if ($resourceItems !== []) {
            $resourcePath = implode('/', $resourceItems);
            $resourcePath .= '/';
        }

		return "{$this->baseApiPath}{$resourcePath}api/json";
	}
}

