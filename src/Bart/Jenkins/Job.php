<?php
namespace Bart\Jenkins;

use Bart\Log4PHP;
use Bart\Primitives\Strings;

/**
 * Interface to Jenkins jobs
 */
class Job
{
    private $defaultParams = [];
    private $myBuildId;
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
     * @param string $projectPath This parameter specifies the location of the Jenkins Job.
     * For example, if your Job is defined in the following third level project: Base->Build->Example,
     * you must pass in the full path to the project, 'job/Base/job/Build/job/Example'.
     */
    public function __construct(Connection $connection, $projectPath)
    {
        if (!is_string($projectPath)) {
            throw new \InvalidArgumentException('The projectPath must be of type string');
        }
        $this->connection = $connection;
        $this->logger = Log4PHP::getLogger(__CLASS__);

        if (!Strings::startsWith($projectPath, '/')) {
            $projectPath = "/{$projectPath}";
        }

        if (Strings::endsWith($projectPath, '/')) {
            $projectPath = substr($projectPath, 0, strlen($projectPath) - 1);
        }

        $projects = explode('/job/', $projectPath);
        unset($projects[0]);

        $this->baseApiPath = '/';
        foreach ($projects as $project) {
            $projectEncoded = rawurlencode($project);
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
                $params = (array)$properties[$i]['parameterDefinitions'];
                break;
            }
        }

        if ($params === null) {
            throw new \InvalidArgumentException("The parameterDefinitions are not part of the data.");
        }

        foreach ($params as $p => $param) {
            $default = $param['defaultParameterValue'];
            $this->defaultParams[$default['name']] = $default['value'];
        }
    }

    /**
     * @return true if the last build was successful
     */
    public function isHealthy()
    {
        // TODO default if property is not defined?
        $lastSuccess = $this->metadata['lastSuccessfulBuild']['number'];
        $lastCompleted = $this->metadata['lastCompletedBuild']['number'];

        // Another alternative is lastBuild.result == 'SUCCESS'
        return $lastSuccess === $lastCompleted;
    }

    /**
     * @see isHealthy()
     * @deprecated
     */
    public function is_healthy()
    {
        return $this->isHealthy();
    }

    /**
     * Enqueue a build with Jenkins
     *
     * @param array $buildParams Any param values to override the project defaults
     * @throws JenkinsApiException
     */
    public function start(array $buildParams = [])
    {
        $lastCompletedBuildId = $this->lastBuildId(true);
        $this->logger->debug('Last completed build: ' . $lastCompletedBuildId);

        $params_json = $this->buildParamsJson($buildParams);
        $this->postJson(
            ['build'],
            [
                'json' => $params_json,
                'delay' => '0sec',
            ]);

        // This gives back the general information about job
        $metadata = $this->getJson([]);

        if ($metadata['inQueue'] > 0) {
            // Build is queued, but must wait and hasn't been assigned a number
            $this->myBuildId = $metadata['nextBuildNumber'];
            $this->logger->debug('Queued build: ' . $this->myBuildId);

            // @TODO Sleep until build should be running?
            // sleep($metadata['queueItem']['buildableStartMilliseconds']);
        } else {
            // If no builds blocking (system wide), this build starts right away
            // ...and has been assigned a build number
            $this->myBuildId = $this->lastBuildId(false);
            $this->logger->debug('Started build: ' . $this->myBuildId);
        }

        if ($lastCompletedBuildId == $this->myBuildId) {
            throw new JenkinsApiException('Could not create new jenkins job. Quitting.');
        }

        return $this->myBuildId;
    }

    /**
     * Build number of the last build
     * @param $completed - If true, get last *completed* build, otherwise last build
     */
    private function lastBuildId($completed = false)
    {
        $buildType = $completed ? 'lastCompletedBuild' : 'lastBuild';

        $lastBuildData = $this->getJson([$buildType]);

        return $lastBuildData['number'];
    }

    /**
     * @return string Success, Failure, or Incomplete
     */
    public function queryStatus()
    {
        $jobData = $this->getJson(array("{$this->myBuildId}"));

        return $jobData['result'];
    }

    /**
     * @see queryStatus()
     * @deprecated
     */
    public function query_status()
    {
        return $this->queryStatus();
    }

    /**
     * Keep polling jenkins every $pollPeriod seconds until build is complete
     * @param int $pollPeriod
     * @param int $timeoutAfter Maximum total minutes to poll before giving up
     */
    public function waitUntilComplete($pollPeriod = 1, $timeoutAfter = 45)
    {
        // Poll jenkins until last build number is our build number or greater
        $lastCompletedBuild = $this->lastBuildId(true);
        $started = time();
        while ($lastCompletedBuild < $this->myBuildId
            && time() < (60 * $timeoutAfter) + $started) {
            sleep($pollPeriod);

            // Consider persisting curl handle between these requests
            // ...if we see perf. degredation
            $lastCompletedBuild = $this->lastBuildId(true);
        }
    }

    /**
     * @see waitUntilComplete()
     * @deprecated
     */
    public function wait_until_complete($pollPeriod = 1, $timeoutAfter = 45)
    {
        $this->waitUntilComplete($pollPeriod, $timeoutAfter);
    }

    /**
     * Curl Jenkins API for details about job
     * @param $resourceItems - List of strings defining path to job resource
     * @return array JSON data decoded as PHP array
     */
    private function getJson(array $resourceItems)
    {
        return $this->connection->curlJenkinsApi($this->buildApiPath($resourceItems), null);
    }

    /**
     * @see getJson but with POST data
     * @param array $resourceItems
     * @param array $httpPostArray
     * @return array JSON data decoded as PHP array
     */
    private function postJson(array $resourceItems, array $httpPostArray)
    {
        return $this->connection->curlJenkinsApi($this->buildApiPath($resourceItems), $httpPostArray);
    }

    /**
     * The JSON representation of the build parameters expected by the job
     * coalescing defaults and $override values
     * @param array $override Override the default values defined by project
     * @return string JSON representation of build parameters
     */
    private function buildParamsJson(array $override)
    {
        $params = [];
        foreach ($this->defaultParams as $name => $value) {
            $params[] = [
                'name' => $name,
                'value' => isset($override[$name]) ? $override[$name] : $value,
            ];
        }

        $jenkinsParams = ['parameter' => $params];
        return json_encode($jenkinsParams);
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

