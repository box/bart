<?php
namespace Bart;

/**
 * Make curl requests
 */
class Curl
{
	private $hostUri;
	private $port;
	private $opts = array();

	/**
	 * Curl to $hostUri on $port
	 */
	public function __construct($hostUri, $port = 80)
	{
		$this->hostUri = $hostUri;
		$this->port = $port;
	}

	/**
	 * Set any authentication credentials desired for request(s)
	 * @param string $user
	 * @param string $pwd
	 * @param int $method (optional) Curl constant for the auth method type
	 */
	public function setAuth($user, $pwd, $method = CURLAUTH_BASIC)
	{
		$this->opts[CURLOPT_HTTPAUTH] = $method;
		$this->opts[CURLOPT_USERPWD] = "$user:$pwd";
	}

	/**
	 * Fall back method to set your own curl options not currenctly
	 * explicitly support
	 *
	 * @param array $curlOpts An array specifying which options to set and
	 * their values. The keys must be valid curl_setopt() constants or
	 * their integer equivalents.
	 * @link http://www.php.net/manual/en/function.curl-setopt-array.php
	 */
	public function setPhpCurlOpts(array $curlOpts)
	{
		// Overwrite any existing values
		foreach ($curlOpts as $optName => $value) {
			$this->opts[$optName] = $value;
		}
	}

	public function get($path, array $getParams, array $headers = null, $cookies = null)
	{
		return $this->request(null, $path,
			$getParams, null, $headers, $cookies);
	}

	/**
	 * @param string $path relative path from base hostUri
	 * @param array $getParams An associative array of get parameters
	 * @param [array,string] $postData The data to send in your post
	 *
	 * @return string Remote response body
	 */
	public function post($path, array $getParams, $postData, array $headers = null, $cookies = null)
	{
		return $this->request(CURLOPT_POST, $path,
			$getParams, $postData, $headers, $cookies);
	}

	/**
	 * PUT a json body
	 *
	 * @param string $path relative path from base hostUri
	 * @param array $getParams An associative array of get parameters
	 * @param mixed $body Optional array or string request body data to send
	 * @param array $headers Optional headers to send with PUT
	 *
	 * @return string Remote response body
	 */
	public function put($path, array $getParams, $body = null, array $headers = null, $cookies = null)
	{
		return $this->request(CURLOPT_PUT, $path,
			$getParams, $body, $headers, $cookies);
	}

	private function request($httpMethod, $path, array $getParams, $body, array $headers = null, $cookies = null)
	{
		$uri = $this->buildFullUri($path, $getParams);
		print "full uri: $uri";
		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_PORT, $this->port);

		if ($headers != null)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		if ($cookies)
		{
			curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		}

		if (isset($httpMethod))
		{
			// Hey, guess what? Curl option PUT won't work!
			// http://stackoverflow.com/questions/5043525/php-curl-http-put
			if ($httpMethod == CURLOPT_PUT)
			{
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			}
			else
			{
				curl_setopt($ch, $httpMethod, true);
			}

			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		// Do not output the contents of the call, instead return in a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		// Set all user defined options last
		curl_setopt_array($ch, $this->opts);

		//make this explicit and override user-input
		//they should get headers through the return array
		curl_setopt($ch, CURLOPT_HEADER, true);

		$returnContent = curl_exec($ch);
		$info = curl_getinfo($ch);

		if ((curl_errno($ch) != 0) || ($returnContent === FALSE))
		{
			$error = curl_error($ch);
			curl_close($ch);
			throw new \Exception("Error posting to $uri, curl error: $error");
		}

		curl_close($ch);


		list($headers_string,$content) = explode("\r\n\r\n", $returnContent);

		$headers = $this->parseHeaders($headers_string);

		print $headers_string;
		return array(
			'info' => $info,
			'content' => $content,
			'headers' => $headers
		);
	}

	private function parseHeaders($headerString)
	{
		preg_match_all("/^([-a-zA-Z0-9_]+): (.+)$/m",$headerString,$matches,PREG_SET_ORDER);
		$headers = array();
		foreach($matches as $m)
		{
			$header = $m[1];
			$value = $m[2];

			//check for duplicate headers and group into arrays
			//necessary for Set-Cookie in particular
			if(array_key_exists($m[1],$headers))
			{

				if(is_array($headers[$header]) )
				{
					$headers[$header][] = $value;
				}
				else
				{
					// take the key -> value and set it to
					// key -> array
					// this matches the pattern of
					// http://php.net/manual/en/function.http-parse-headers.php
					$oldValue = $headers[$header];
					$headers[$header] = array($oldValue, $value);
				}
			}
			else
			{
				$headers[$header] = $value;
			}
		}
		return $headers;
	}

	/**
	 * build the full URI request
	 * @param string $path the path portion of the URI provided with this request
	 * @param string[] $getVars array of GET params
	 * @return string full hostUri including GET params
	 */
	private function buildFullUri($path, $getVars)
	{
		$fullPath = $this->buildFullPath($path);

		if (!$getVars) {
			return $fullPath;
		}

		$query = http_build_query($getVars);

		// does the URI have existing query params?
		if(strpos($fullPath, '?') !== false) {
			return $fullPath . "&$query";
		}

		// Default; the path is just the path with no params
		return $fullPath . "?$query";

	}

	private function buildFullPath($subUri)
	{
		if (!$subUri) {
			return $this->hostUri;
		}

		$hostHasSlash = (substr($this->hostUri, -1) == "/");
		$subHasSlash = (substr($subUri, 0, 1) == "/");

		if ($hostHasSlash && $subHasSlash) {
			// leave the trailing slash, remove the leading
			return sprintf("%s%s",$this->hostUri,substr($subUri,1));
		}

		if(!$hostHasSlash && !$subHasSlash) {
			return sprintf("%s/%s",$this->hostUri, $subUri);
		}

		// one has the slash and the other doesn't
		return sprintf("%s%s",$this->hostUri,$subUri);
	}
}
