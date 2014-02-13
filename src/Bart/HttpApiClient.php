<?php
namespace Bart;

/**
 * A wrapper around Bart\Curl to make it more session aware and API friendly
 *
 * @author Jeremy Pollard <jpollard@box.com>
 */ 
class HttpApiClient
{
	/** @var string http auth username */
	protected $username = "";

	/** @var string http auth password */
	protected $password = "";

	protected $authMethod = null;

	/** @var  string[] headers to include with all requests */
	protected $globalHeaders = array();

	/** @var  string[] getVars to include with all requests */
	protected $globalGetVars = array();

	/** What about POST? not creating global ones since a body may be included? maybe later */

	/** @var  string the base hostUri to base all requests off of */
	protected $baseUri;

	/** @var int default connection timeout */
	protected $timeout = 15;

	/** @var  string[] string holding the cookies! */
	protected $cookies = null;

	/** @var bool true to track cookies automatically, false to ignore recieved cookies */
	protected $trackCookies = true;

	/** @var bool enable/disable ssl peer verification, enabled by default */
	protected $sslPeerVerification = true;



	/**
	 * @param string $baseUri base hostUri for all requests
	 */
	public function __construct($baseUri)
	{
		$this->baseUri = $this->validateBaseUri($baseUri);
	}


	/**
	 * @param $username
	 * @param $password
	 * @param int $method
	 * @throws HttpApiClientException
	 */
	public function setAuth($username,$password, $method = CURLAUTH_BASIC)
	{
		if(!is_string($username))
		{

			throw new HttpApiClientException("Username must be a string");
		}

		if(!is_string($password))
		{
			throw new HttpApiClientException("Password must be a string");
		}

		$authMethods = array(CURLAUTH_ANY, CURLAUTH_BASIC, CURLAUTH_ANYSAFE, CURLAUTH_DIGEST,
			CURLAUTH_GSSNEGOTIATE, CURLAUTH_NTLM);

		if(!in_array($method, $authMethods))
		{
			throw new HttpApiClientException("Auth method must be a valid CURLAUTH_ method");
		}

		$this->username = $username;
		$this->password = $password;
		$this->authMethod = $method;
	}

	public function setGlobalTimeout($timeout)
	{
		if(is_numeric($timeout) && $timeout > 0)
		{
			$this->timeout = $timeout;
		}
		else
		{
			throw new HttpApiClientException("Timeout must be an integer > 0");
		}
	}


	/**
	 * Set Global 'Get' variables to be sent with every request
	 * @param string[] $globalGetVars
	 * @throws HttpApiClientException
	 */
	public function setGlobalGetVars($globalGetVars)
	{
		if($this->validateKeyValueArray($globalGetVars))
		{
			$this->globalGetVars = $globalGetVars;

		}
		elseif($globalGetVars === null)
		{
			$this->globalGetVars = array();
		}
		else
		{
			throw new HttpApiClientException('$globalGetVars must be a key=> value array. no objects, multi-D arrays, etc');
		}
	}

	/**
	 * Get the list of current global Get variables
	 * @return \string[]
	 */
	public function getGlobalGetVars()
	{
		return $this->globalGetVars;
	}

	/**
	 * Set Global Headers to be sent with each request
	 * @param string[] $globalHeaders
	 * @throws HttpApiClientException
	 */
	public function setGlobalHeaders($globalHeaders)
	{
		if($this->validateKeyValueArray($globalHeaders))
		{
			$this->globalHeaders = $globalHeaders;

		}
		elseif( $globalHeaders === null)
		{
			$this->globalHeaders = array();
		}
		else
		{
			throw new HttpApiClientException('$globalHeaders must be a key=> value array. no objects, multi-D arrays, etc');
		}

	}

	/**
	 * Get the list of global headers
	 * @return \string[]
	 */
	public function getGlobalHeaders()
	{
		return $this->globalHeaders;
	}

	/**
	 * Enable/Disable ssl peer verification
	 * @param bool $verify enable disable peer verification
	 * @throws HttpApiClientException
	 */
	public function sslPeerVerification($verify)
	{
		if(!is_bool($verify))
		{
			throw new HttpApiClientException("expects boolean");
		}

		$this->sslPeerVerification = $verify;
	}

	/**
	 * Enable or disable cookie tracking. (Enabled by default.)
	 * Tracked cookies will be included with subsequent requests.
	 * @param bool $track When true, track all cookies sent from server; otherwise ignore cookies
	 * @throws HttpApiClientException
	 */
	public function trackCookies($track)
	{
		if(!is_bool($track))
		{
			throw new HttpApiClientException("expects boolean true or false");
		}

		$this->trackCookies = $track;

	}

	/**
	 * @param array $cookies
	 * @throws HttpApiClientException
	 */
	public function setCookies(array $cookies)
	{
		if($this->validateKeyValueArray($cookies) || $cookies === null)
		{
			$this->cookies = $cookies;

		}
		else
		{
			throw new HttpApiClientException('$cookies must be a key=>value array or null');
		}
	}

	/**
	 * @return string[]
	 */
	public function getCookies()
	{
		return $this->cookies;
	}

	/**
	 * Perform a HTTP Delete request
	 * @param string $path
	 * @param string[] $getVars
	 * @param string[] $headers
	 * @param int $timeout
	 * @return HttpApiClientResponse
	 */
	public function delete($path = "/", $getVars = null, $headers = null, $timeout = null)
	{
		$curler = $this->initCurl();
		$this->setTimeout($timeout, $curler);

		$validatedGetVars = $this->getValidatedGetVars($getVars);
		$validatedHeaders = $this->getValidatedHeaders($headers);

		$response = $curler->delete($path,
			$validatedGetVars,
			$validatedHeaders,
			$this->cookies
		);

		return $this->processResponse($response);

	}

	/**
	 * Perform a HTTP Get request
	 * @param string $path
	 * @param string[] $getVars
	 * @param string[] $headers All the header strings, e.g. 'Accept: application/json'
	 * @param int $timeout
	 * @return HttpApiClientResponse
	 */
	public function get($path = "/", $getVars = null, $headers = null, $timeout = null)
	{
		$curler = $this->initCurl();
		$this->setTimeout($timeout, $curler);

		$validatedGetVars = $this->getValidatedGetVars($getVars);
		$validatedHeaders = $this->getValidatedHeaders($headers);

		$response = $curler->get($path,
			$validatedGetVars,
			$validatedHeaders,
			$this->cookies
		);

		return $this->processResponse($response);

	}

	/**
	 * Perform a HTTP Post request
	 * @param string $path
	 * @param string[] $getVars
	 * @param string|string[] $postVars
	 * @param string[] $headers
	 * @param int $timeout
	 * @return HttpApiClientResponse
	 */
	public function post($path = "/", $getVars = null, $postVars = null, $headers = null, $timeout = null)
	{
		$curler = $this->initCurl();
		$this->setTimeout($timeout, $curler);

		$validatedGetVars = $this->getValidatedGetVars($getVars);
		$validatedHeaders = $this->getValidatedHeaders($headers);

		$response = $curler->post($path,
			$validatedGetVars,
			$this->getValidatedPostVars($postVars),
			$validatedHeaders,
			$this->cookies
		);

		return $this->processResponse($response);
	}

	/**
	 * Perform a HTTP Put request
	 * @param string $path
	 * @param string[] $getVars
	 * @param string|string[] $postVars
	 * @param string[] $headers
	 * @param int $timeout
	 * @return HttpApiClientResponse
	 */
	public function put($path = "/", $getVars = null, $postVars = null, $headers = null, $timeout = null)
	{
		$curler = $this->initCurl();
		$this->setTimeout($timeout, $curler);

		$validatedGetVars = $this->getValidatedGetVars($getVars);
		$validatedHeaders = $this->getValidatedHeaders($headers);

		$response = $curler->put($path,
			$validatedGetVars,
			$this->getValidatedPostVars($postVars),
			$validatedHeaders,
			$this->cookies
		);

		return $this->processResponse($response);
	}


	/**
	 * Process the response array and return a response object.
	 * Also handles cookies sent by the server
	 * @param array $response response array from \Bart\Curl
	 * @return HttpApiClientResponse
	 */
	private function processResponse(array $response)
	{
		if($this->trackCookies)
		{
			if(array_key_exists('Set-Cookie', $response['headers']))
			{
				if($this->cookies === null )
				{
					$this->cookies = array();
				}
				foreach($response['headers']['Set-Cookie'] as $c)
				{
					$keyValue = strstr($c,';', true);
					list($key, $value) = explode('=', $keyValue);
					$this->cookies[$key] = $value;
				}
			}
		}

		return new HttpApiClientResponse(
			$response['info']['http_code'],
			$response['content'],
			$response['headers']
		);
	}


	/**
	 * Ensures that the headers array contains only strings
	 * @param string[] $headers
	 * @return string[]
	 * @throws HttpApiClientException
	 */
	private function getValidatedHeaders($headers)
	{
		// no headers sent, return the global ones
		if($headers === null)
		{
			return $this->globalHeaders;
		}

		foreach ($headers as $header)
		{
			if (!is_string($header))
			{
				throw new HttpApiClientException('Header must be an array of strings');
			}
		}

		return array_merge($this->globalHeaders, $headers);
	}

	/**
	 * return a set of validated get vars
	 * ensure a single dimensional array of strings
	 * null is okay too
	 * @param $getVars
	 * @return array
	 * @throws HttpApiClientException
	 */
	private function getValidatedGetVars($getVars)
	{
		if($getVars === null)
		{
			return $this->globalGetVars;
		}

		if(!$this->validateKeyValueArray($getVars) && $getVars !== null)
		{
			throw new HttpApiClientException("Invalid Get Vars: " . print_r($getVars, true));
		}

		//merge instance getVars with globalGetVars
		return array_merge($this->globalGetVars, $getVars);
	}


	/**
	 * return validated post vars
	 * ensure array of strings or simply a string
	 * @param $postVars
	 * @return mixed
	 * @throws HttpApiClientException
	 */
	private function getValidatedPostVars($postVars)
	{
		if(!$this->validateKeyValueArray($postVars) && gettype($postVars) != 'string' && $postVars !== null)
		{
			throw new HttpApiClientException("Invalid Post Vars: " . print_r($postVars, true));
		}

		return $postVars;
	}

	/**
	 * Validate whether an array is a valid key->value strucure and not multidimensional
	 * and does not contain invalid type (objects,resource,array)
	 * @param string[]|int[]|bool[]|float[]|double[] $array the array to validate
	 * @return bool true if the array is a valid key value structure
	 */
	protected function validateKeyValueArray($array)
	{
		if(gettype($array) == "array")
		{
			$invalidTypes = array('array', 'object', 'resource', 'NULL');
			foreach($array as $key => $value)
			{

				if(in_array(gettype($key),$invalidTypes) || in_array(gettype($value),$invalidTypes))
				{
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * validate the base URI
	 * @param string $uri hostUri to validate
	 * @return string the validated URI in all lower case
	 * @throws HttpApiClientException Invalid URI
	 */
	protected function validateBaseUri($uri)
	{
		$lower_uri = strtolower($uri);
		# [-\w\.]+(:[\d]+)?(/[\w]+)?/|
		if(!preg_match('|^http(s)?://[-\w\.]+(:[\d]+)?(/[-\w\.]*)?$|',$lower_uri) )
		{
			throw new HttpApiClientException("Invalid URI: $uri");
		}

		return $lower_uri;
	}


	/**
	 * initialized the curl handler
	 */
	protected function initCurl()
	{
		// Set default port and update if specified
		$port = (substr($this->baseUri, 0, 5) == 'https') ? 443 : 80;
		if(preg_match('#http[s]?://[^:]+:(\d+)($|/)#',$this->baseUri, $matches))
		{
			$port = $matches[1];
		}

		// Bart\Curl does no validation of URLS and simply concats hostURI and path
		// so we will just pass fully validated URI with request
		/** @var \Bart\Curl curler */
		$curler = Diesel::create('\Bart\Curl', $this->baseUri, $port);

		if($this->authMethod !== null)
		{
			$curler->setAuth($this->username, $this->password, $this->authMethod);
		}

		$curlOpts = array(CURLOPT_TIMEOUT => $this->timeout,
							CURLOPT_HEADER => true);
		if(!$this->sslPeerVerification)
		{
			$curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
		}

		$curler->setPhpCurlOpts($curlOpts);

		return $curler;

	}

	/**
	 * Set the timeout for curl attempts in seconds
	 * @param int $timeout timeout in seconds, must be > 0
	 * @param \Bart\Curl $curler curl object to set timeout on
	 * @throws HttpApiClientException Invalid Timeout in the event that the timeout is non-numeric or non > 0
	 */
	protected function setTimeout($timeout, $curler)
	{
		if(is_int($timeout) && $timeout > 0)
		{
			$curler->setPhpCurlOpts(array(CURLOPT_TIMEOUT => $timeout));
		}
		elseif($timeout !== null)
		{
			throw new HttpApiClientException("Invalid timeout: '$timeout'");
		}
	}





}


class HttpApiClientResponse
{
	/** @var  int the HTTP response code */
	protected $http_code;

	/** @var  string the body of the response */
	protected $body;

	/** @var  string[] array of headers */
	protected $headers;

	/**
	 * @param int $http_code
	 * @param string $body
	 * @param string[] $headers
	 */
	public function __construct($http_code, $body, $headers)
	{
		$this->http_code = $http_code;
		$this->body = $body;
		$this->headers = $headers;
	}

	/**
	 * @return string
	 */
	public function get_body()
	{
		return $this->body;
	}

	/**
	 * @return \string[]
	 */
	public function get_headers()
	{
		return $this->headers;
	}

	/**
	 * @return int
	 */
	public function get_http_code()
	{
		return $this->http_code;
	}


}


class HttpApiClientException extends \Exception
{

}