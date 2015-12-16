<?php
namespace Bart\Jenkins;

use Bart\Diesel;
use Bart\JSON;
use Bart\Log4PHP;
use Bart\Primitives\Strings;

/**
 * Class Connection
 * Manages interface between remote Jenkins REST API and client code
 * @package Bart\Jenkins
 */
class Connection
{

    /** @var \Logger */
    private $logger;
    /** @var array $curlOptions */
    private $curlOptions;
    /** @var int $port */
    private $port;
    /** @var string $baseUrl */
    private $baseUrl;

    /**
     * Connection constructor.
     * @param string $domain
     * @param string $protocol 'http' or 'https'.
     * @param int $port The port can be generally be determined by the $protocol. 'http' corresponds
     * to port 8080, while 'https' corresponds to 443. If the $port is passed in, it will override
     * that determination.
     */
    public function __construct($domain, $protocol = 'http', $port = null)
    {
        if (!in_array($protocol, ['http', 'https'])) {
            throw new \InvalidArgumentException("The protocol must only be 'http' or 'https'");
        }

        $this->logger = Log4PHP::getLogger(__CLASS__);
        $this->curlOptions = [];
        $this->port = $port;
        if ($this->port === null) {
            if ($protocol === 'http') {
                $this->port = 8080;
            } else {
                $this->port = 443;
            }
        }

        $this->baseUrl = "{$protocol}://{$domain}:{$this->port}";
        $this->logger->debug('Base URL: ' . $this->baseUrl);
    }

    /**
     * Set Jenkins authentication
     * NOTE: Authentication must be set if the Jenkins instance doesn't allow
     * anonymous connections.
     * @param string $user User to authenticate against.
     * @param string $token API token corresponding to user.
     */
    public function setAuth($user, $token)
    {
        $this->curlOptions[CURLOPT_USERPWD] = "{$user}:{$token}";
    }

    /**
     * Curl Jenkins JSON API
     *
     * NOTE: This method is not meant to be used on its own. It is used by other classes,
     * e.g. \Bart\Jenkins\Job, to make API calls against Jenkins.
     *
     * @param string $apiPath The full API path to curl against. For example, to do a
     * simple GET against the Jenkins Job 'Example', the full path, 'job/Example/api/json'
     * must be passed in.
     * @param array $postData if null, then curl uses GET, otherwise POSTs data
     * @return array JSON data decoded as PHP array
     * @throws JenkinsApiException
     */
    public function curlJenkinsApi($apiPath, array $postData = null)
    {
        if (!Strings::startsWith($apiPath, '/')) {
            $apiPath = "/{$apiPath}";
        }

        $fullUrl = "{$this->baseUrl}{$apiPath}";
        $isPost = ($postData !== null);
        $this->logger->debug('Curling ' . ($isPost ? 'POST ' : 'GET ') . $fullUrl);

        /** @var \Bart\Curl $curl */
        $curl = Diesel::create('\Bart\Curl', $fullUrl, $this->port);
        if ($this->curlOptions !== []) {
            $curl->setPhpCurlOpts($this->curlOptions);
        }
        $response = $isPost ?
            $curl->post('', [], $postData) :
            $curl->get('', []);

        $httpCode = $response['info']['http_code'];
        $content = $response['content'];
        if ($httpCode !== 200 && $httpCode !== 201 && $httpCode !== 202) {
            throw new JenkinsApiException("The Jenkins API call returned a {$httpCode}, " .
                "with the following content: {$content}");
        }
        return JSON::decode($content);
    }
}
