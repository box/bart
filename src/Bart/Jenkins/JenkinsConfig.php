<?php
namespace Bart\Jenkins;


use Bart\Configuration\Configuration;
use Bart\Configuration\ConfigurationException;

class JenkinsConfig extends Configuration
{

    /**
     * @return string Sample of how configuration is intended to be defined
     */
    public function README()
    {
        return <<<README
; domain and location are the only required fields
[api]
domain = 'jenkins.example.com'
; Defaults to 'http'
protocol = 'http'
; Defaults to 8080
port = 8080
; user & token default to null for anonymous connections
user = 'example-user'
token = 'example-token'
[job]
; location specifies the location of the Jenkins job, e.g. if your job is defined in the
; third level project: Base->Build->Example, the location is specified as below.
location = 'job/Base/job/Build/job/Example'

README;
    }

    /**
     * @return string The domain of the Jenkins instance
     * @throws ConfigurationException
     */
    public function domain()
    {
        return $this->getValue('api', 'domain');
    }

    /**
     * @return string Transfer protocol (http or https)
     * @throws ConfigurationException
     */
    public function protocol()
    {
        return $this->getValue('api', 'protocol', 'http', false);
    }

    /**
     * @return string The port that the Jenkins instance is running on
     * @throws ConfigurationException
     */
    public function port()
    {
        return $this->getValue('api', 'port', 8080, false);
    }

    /**
     * @return string Jenkins API user
     * @throws ConfigurationException
     */
    public function user()
    {
        return $this->getValue('api', 'user', null, false);
    }

    /**
     * @return string Jenkins API token corresponding to the Jenkins API user
     * @throws ConfigurationException
     */
    public function token()
    {
        return $this->getValue('api', 'token', null, false);
    }

    /**
     * @return string The location of the Jenkins job.
     * @throws ConfigurationException
     */
    public function jobLocation()
    {
        return $this->getValue('job', 'location', null, false);
    }

}
