<?php
namespace Bart;

/**
 * Please provide a description
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

    /** @var  \Bart\Curl curl object to use for curl interactions*/
    protected $curler;

	/** @var  string[] string holding the cookies! */
	protected $cookies = array();

	/** @var bool true to track cookies automatically, false to ignore recieved cookies */
	protected $trackCookies = true;



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
		if(is_string($username))
		{

			throw new HttpApiClientException("Username must be a string");
		}

		if(is_string($password))
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
	 * @param \string[] $globalGetVars
	 * @throws HttpApiClientException
	 */
	public function setGlobalGetVars($globalGetVars)
	{
		if($this->validateKeyValueArray($globalGetVars) || $globalGetVars === null)
		{
			$this->globalGetVars = (gettype($globalGetVars) == "array")? $globalGetVars : array();

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
	 * @param \string[] $globalHeaders
	 */
	public function setGlobalHeaders($globalHeaders)
	{
		if($this->validateKeyValueArray($globalHeaders) || $globalHeaders === null)
		{
			$this->globalHeaders = (gettype($globalHeaders) == "array")? $globalHeaders : array();

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
	 * Sets the tack cookies flag.
	 * If true, automatically track cookies sent from the server
	 * If flase, do not track cookies sent from the server
	 * @param $track
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

	public function setCookies(array $cookies)
	{
		if($this->validateKeyValueArray($cookies) || $cookies === null)
		{
			$this->cookies = (gettype($cookies) == "array")? $cookies : array();

		}
		else
		{
			throw new HttpApiClientException('$cookies must be a key=>value array or null');
		}
	}

	/**
	 * Perform a HTTP Get request
	 * @param string $path
	 * @param null|string[] $getVars
	 * @param null|string[] $headers
	 * @param null|int $timeout
	 * @return HttpApiClientResponse
	 */
	public function get($path = "/", $getVars = null, $headers = null, $timeout = null)
	{
		$this->initCurl();
		$this->setTimeout($timeout);

		$validatedGetVars = $this->getValidatedGetVars($getVars);
		$validatedHeaders = $this->getValidatedHeaders($headers);

		//if we haven't set any cookies, provide null
		$cookies = (count($this->cookies) > 0) ? $this->cookies : null;

		$response = $this->curler->get($path,
			$validatedGetVars,
			$validatedHeaders,
			$cookies
		);

		return $this->processResponse($response);

	}

	/**
	 * Perform a HTTP Post request
	 * @param string $path
	 * @param null|string[] $getVars
	 * @param null|string|string[] $postVars
	 * @param null|string[] $headers
	 * @param null|int $timeout
	 * @return HttpApiClientResponse
	 */
	public function post($path = "/", $getVars = null, $postVars = null, $headers = null, $timeout = null)
	{
		$this->initCurl();
		$this->setTimeout($timeout);

		$validatedGetVars = $this->getValidatedGetVars($getVars);

		$validatedHeaders = $this->getValidatedHeaders($headers);

		//if we haven't set any cookies, provide null
		$cookies = (count($this->cookies) > 0) ? $this->cookies : null;

		$response = $this->curler->post($path,
			$validatedGetVars,
			$this->getValidatedPostVars($postVars),
			$validatedHeaders,
			$cookies
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
		$tempCookies = array();
		if(array_key_exists('Set-Cookie', $response['headers']))
		{
			foreach($response['headers']['Set-Cookie'] as $c)
			{
				$keyValue = strstr($c,';', true);
				list($key, $value) = explode('=', $keyValue);
				$tempCookies[$key] = $value;
			}
		}

		//merge cookies
		if($this->trackCookies)
		{
			$this->cookies = array_merge($this->cookies,$tempCookies);
		}
		return new HttpApiClientResponse(
			$response['info']['http_code'],
			$response['content'],
			$response['headers']
		);
	}


	/**
	 * return a set of validated headers.
	 * ensure a single dimensional array of strings
	 * @param $headers
	 * @return array
	 * @throws HttpApiClientException
	 */
	private function getValidatedHeaders($headers)
	{
		if(!$this->validateKeyValueArray($headers) && $headers !== null)
		{
			throw new HttpApiClientException("Invalid Headers: " . print_r($headers, true));
		}

		if($headers === null)
		{
			$headers = array();
		}

		return array_merge($this->globalHeaders,$headers);
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
		if(!$this->validateKeyValueArray($getVars) && $getVars !== null)
		{
			throw new HttpApiClientException("Invalid Get Vars: " . print_r($getVars, true));
		}

		if($getVars === null)
		{
			$getVars = array();
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
		if( !is_null($this->curler) )
		{
			//close it just to be safe
			$this->curler = null;
		}

		if($this->authMethod !== null)
		{
			$this->curler->setAuth($this->username, $this->password, $this->authMethod);
		}

		$port = 80;
		//#http(s)?://{^:]+:(\d+)($|/)#
		if(preg_match('#http[s]?://[^:]+:(\d+)($|/)#',$this->baseUri, $matches))
		{
			$port = $matches[1];
		}

		//Bart\Curl does no validation of URLS and simply concats hostURI and path
		//so will just path fully validated URI with request

		/** @var \Bart\Curl curler */
		$this->curler = Diesel::create('\Bart\Curl', $this->baseUri, $port);


		$this->curler->setPhpCurlOpts(array(
			CURLOPT_TIMEOUT => $this->timeout,
			CURLOPT_SSL_VERIFYPEER => false
		));

	}

	/**
	 * Set the timeout for curl attempts in seconds
	 * @param int $timeout timeout in seconds, must be > 0
	 * @throws HttpApiClientException Invalid Timeout in the event that the timeout is non-numeric or non > 0
	 */
	protected function setTimeout($timeout)
	{
		if($this->curler === null)
		{
			$this->initCurl();
		}
		if(is_int($timeout) && $timeout > 0)
		{
			$this->curler->setPhpCurlOpts(array(CURLOPT_TIMEOUT => $timeout));
		}
		elseif($timeout !== null)
		{
			throw $this->newException("Invalid timeout: '$timeout'");
		}
	}

	/**
	 * @return string[]
	 */
	public function getCookies()
	{
		return $this->cookies;
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