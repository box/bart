<?php
namespace Bart\Jenkins;

use Bart\Diesel;
use Bart\Log4PHP;

/**
 * Interface to Jenkins jobs
 */
class Job
{
	private $baseJobUrl;
	private $default_params = [];
	private $my_build_id;
	private $metadata;
	/** @var \Logger */
	private $logger;

	/** @var array $curlOptions */
	private $curlOptions;
	/** @var string $port */
	private $port;



	/**
	 * Job constructor. Loads metadata about a project.
	 * @param string $domain The
	 * @param string $baseProject The top-level name of your Project.
	 * @param array $subProjects If your Jenkins Job is not defined in the top-level project,
	 * this parameter must be passed in. For example, if your Job is defined in the following
	 * third level project: Base->Build->Example, you must pass in 'Base' as the $baseProject
	 * parameter, and the array ['Build', 'Example'] as the $subProjects parameter. The order
	 * of the array is maintained when loading the metadata, and hence it's critical.
	 * @param string $protocol 'http' or 'https'.
	 * @param string $user User to authenticate against.
	 * @param string $token API token corresponding to user.
	 * NOTE: The $user and $token must be passed in if the Jenkins instance doesn't support
	 * anonymous connections.
	 * @param int $port The port can be generally be determined by the $protocol. 'http' corresponds
	 * to port 8080, while 'https' corresponds to 443. If the $port is passed in, it will override
	 * that determination.
     */
	public function __construct(
		$domain,
		$baseProject,
		array $subProjects = [],
		$protocol = 'http',
		$user = null,
		$token = null,
		$port = 0
	)
	{
		if (!$domain) {
			throw new \InvalidArgumentException('Must provide a valid domain');
		}

		if (!$baseProject) {
			throw new \InvalidArgumentException('Must provide a base job name');
		}

		$this->curlOptions = [];
		$baseProjectEncoded = rawurlencode($baseProject);
		$projectPath = "job/$baseProjectEncoded";
		if ($subProjects !== []) {
			for ($i = 0; $i < count ($subProjects); $i++) {
				$subProjectEncoded = rawurlencode($subProjects[$i]);
				$projectPath .= "/job/$subProjectEncoded";
			}
		}


		if ($protocol !== 'http' && $protocol !== 'https') {
			throw new \InvalidArgumentException("The protocol must only be 'http' or 'https'");
		}

		if ($user !== null || $token !== null) {
			if ($token === null || $user === null ) {
				throw new \InvalidArgumentException("You must specify both a user and a token");
			}
			$this->curlOptions[CURLOPT_USERPWD] = "{$user}:{$token}";
		}

		$this->port = $port;
		if ($this->port === 0) {
			if ($protocol === 'http') {
				$this->port = 8080;
			} else {
				$this->port = 443;
			}
		}

		$this->logger = Log4PHP::getLogger(__CLASS__);

		$this->baseJobUrl = "{$protocol}://{$domain}:{$this->port}/{$projectPath}/";
		$this->logger->debug('Base uri: ' . $this->baseJobUrl);

		$this->metadata = $this->get_json(array());

		if (!$this->metadata['buildable']) {
			throw new \InvalidArgumentException("Project $baseProject is disabled");
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
		return $lastSuccess === $lastCompleted;
	}

	/**
	 * Enqueue a build with Jenkins
	 *
	 * @param array $build_params Any param values to override the project defaults
	 */
	public function start(array $build_params = [])
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

		$last_build_data = $this->get_json(array($build_type));

		return $last_build_data['number'];
	}

	/**
	 * @return string Success, Failure, or Incomplete
	 */
	public function query_status()
	{
		$job_data = $this->get_json(array("{$this->my_build_id}"));

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
		$resource_path = '';
		if ($resource_items !== []) {
			$resource_path = implode('/', $resource_items);
			$resource_path .= '/';
		}

		$url = $this->baseJobUrl . $resource_path . 'api/json';
		$is_post = ($post_data != null);
		$this->logger->debug('Curling ' . ($is_post ? 'POST ' : 'GET ') . $url);

		/** @var \Bart\Curl $c */
		$c = Diesel::create('Bart\Curl', $url, $this->port);
		if ($this->curlOptions !== []) {
			$c->setPhpCurlOpts($this->curlOptions);
		}
		$response = $is_post ?
			$c->post('', array(), $post_data) :
			$c->get('', array());

		$httpCode = $response['info']['http_code'];
		$content = $response['content'];
		if ($httpCode !== 200 && $httpCode !== 201 && $httpCode !== 202 ) {
			throw new JenkinsApiException("The Jenkins API call returned a {$httpCode}, " .
				"with the following content: {$content}");
		}
		$content = $response['content'];
		return json_decode($content, true);
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

