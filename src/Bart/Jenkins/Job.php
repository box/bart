<?php
namespace Bart\Jenkins;

use Bart\Diesel;
use Bart\Log4PHP;

/**
 * Interface to Jenkins jobs
 */
class Job
{
	private $base_job_url;
	private $default_params = array();
	private $my_build_id;
	private $metadata;
	/** @var \Logger */
	private $logger;

	/**
	 * Load metadata about a project
	 */
	public function __construct($domain, $job_name)
	{
		if (!$domain)
		{
			throw new \Exception('Must provide a valid domain');
		}

		if (!$job_name)
		{
			throw new \Exception('Must provide a job name');
		}

		$this->logger = Log4PHP::getLogger(__CLASS__);

		$this->base_job_url = "http://$domain:8080/job/" . rawurlencode($job_name) . '/';
		$this->logger->debug('Base uri: ' . $this->base_job_url);

		$this->metadata = $this->get_json(array());

		if (!$this->metadata['buildable'])
		{
			throw new \Exception("Project $job_name is disabled");
		}

		$this->set_default_params();
	}

	/**
	 * Load the default set of parameters defined by project
	 */
	private function set_default_params()
	{
		$properties = $this->metadata['property'];
		if (count($properties) == 0) return;

		$params = (array) $properties[0]['parameterDefinitions'];

		foreach ($params as $p => $param)
		{
			$default = $param['defaultParameterValue'];
			$this->default_params[$default['name']] = $default['value'];
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
		return $lastSuccess == $lastCompleted;
	}

	/**
	 * Enqueue a build with Jenkins
	 *
	 * @param $build_params Any param values to override the project defaults
	 */
	public function start(array $build_params = array())
	{
		$last_completed_build_id = $this->last_build_id(true);
		$this->logger->debug('Last completed build: ' . $last_completed_build_id);

		$params_json = $this->build_params_json($build_params);
		$this->post_json(
			array('build'),
			array(
				'json' => $params_json,
				'delay' => '0sec',
			));

		// This gives back the general information about job
		$metadata = $this->get_json(array());

		if ($metadata['inQueue'] > 0)
		{
			// Build is queued, but must wait and hasn't been assigned a number
			$this->my_build_id = $metadata['nextBuildNumber'];
			$this->logger->debug('Queued build: ' . $this->my_build_id);

			// @TODO Sleep until build should be running?
			// sleep($metadata['queueItem']['buildableStartMilliseconds']);
		}
		else
		{
			// If no builds blocking (system wide), this build starts right away
			// ...and has been assigned a build number
			$this->my_build_id = $this->last_build_id(false);
			$this->logger->debug('Started build: ' . $this->my_build_id);
		}

		if ($last_completed_build_id == $this->my_build_id)
		{
			throw new \Exception('Could not create new jenkins job. Quitting.');
		}

		return $this->my_build_id;
	}

	/**
	 * Build number of the last build
	 * @param $completed - If true, get last *completed* build, otherwise last build
	 */
	private function last_build_id($completed = false)
	{
		$build_type = $completed ? 'lastCompletedBuild' : 'lastBuild';

		$last_build_data = $this->get_json(array($build_type), true);

		return $last_build_data['number'];
	}

	/**
	 * @returns Success, Failure, or Incomplete
	 */
	public function query_status()
	{
		$job_data = $this->get_json(array("{$this->my_build_id}"));

		return $job_data['result'];
	}

	/**
	 * Keep polling jenkins every $poll_period seconds until build is complete
	 * @param $timeout_after Maximum total minutes to poll before giving up
	 */
	public function wait_until_complete($poll_period = 1, $timeout_after = 45)
	{
		// Poll jenkins until last build number is our build number or greater
		$last_completed_build_id = $this->last_build_id(true);
		$started = time();
		while ($last_completed_build_id < $this->my_build_id
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
	 * @param $resource_items - List of strings defining path to job resource
	 *
	 * E.g. get details of Acceptance Test job 36 with get_json(array('36'))
	 *      ==> http://qa-hudson1.dev/job/Acceptance%20Test/36/api/json
	 */
	private function get_json(array $resource_items)
	{
		return $this->curl($resource_items, null);
	}

	/**
	 * @seealso get_json but with POST data
	 */
	private function post_json(array $resource_items, array $http_post_array)
	{
		return $this->curl($resource_items, $http_post_array);
	}

	/**
	 * Curl Jenkins JSON API
	 *
	 * @param array $resource_items
	 * @param array $post_data if null, then curl uses GET, otherwise POSTs data
	 * @returns array JSON data decoded as PHP array
	 */
	private function curl(array $resource_items, array $post_data = null)
	{
		$resource_path = implode('/', $resource_items);

		$url = $this->base_job_url . $resource_path . '/api/json';
		$is_post = ($post_data != null);
		$this->logger->debug('Curling ' . ($is_post ? 'POST ' : 'GET ') . $url);

		/** @var \Bart\Curl $c */
		$c = Diesel::create('Bart\Curl', $url, 8080);
		$response = $is_post ?
			$c->post('', array(), $post_data) :
			$c->get('', array());

		$jenkins_json = $response['content'];
		return json_decode($jenkins_json, true);
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
		foreach ($this->default_params as $name => $value)
		{
			$params[] = array(
				'name' => $name,
				'value' => isset($override[$name]) ?  $override[$name] : $value,
			);
		}

		$jenkins_params = array('parameter' => $params);

		return json_encode($jenkins_params);
	}
}

