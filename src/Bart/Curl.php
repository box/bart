<?php
namespace Bart;

/**
 * Make curl requests
 */
class Curl
{
	private $uri;
	private $port;
	private $opts = array();

	/**
	 * Curl to $hostUri on $port
	 */
	public function __construct($hostUri, $port = 80)
	{
		$this->uri = $hostUri;
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
		return $this->request(null, $this->uri . $path,
			$getParams, null, $headers, $cookies);
	}

	/**
	 * @param string $path relative path from base uri
	 * @param array $getParams An associative array of get parameters
	 * @param [array,string] $postData The data to send in your post
	 *
	 * @return string Remote response body
	 */
	public function post($path, array $getParams, $postData)
	{
		return $this->request(CURLOPT_POST, $this->uri . $path,
			$getParams, $postData);
	}

	/**
	 * PUT a json body
	 *
	 * @param string $path relative path from base uri
	 * @param array $getParams An associative array of get parameters
	 * @param mixed $body Optional array or string request body data to send
	 * @param array $headers Optional headers to send with PUT
	 *
	 * @return string Remote response body
	 */
	public function put($path, array $getParams, $body = null, array $headers = null)
	{
		return $this->request(CURLOPT_PUT, $this->uri . $path,
			$getParams, $body, $headers);
	}

	private function request($httpMethod, $uri, array $getParams, $body, array $headers = null, $cookies = null)
	{
		$ch = curl_init($uri . '?' . http_build_query($getParams));
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

		$content = curl_exec($ch);
		$info = curl_getinfo($ch);

		if ((curl_errno($ch) != 0) || ($content === FALSE))
		{
			$error = curl_error($ch);
			curl_close($ch);
			throw new \Exception("Error posting to $uri, curl error: $error");
		}

		curl_close($ch);

		return array(
			'info' => $info,
			'content' => $content,
		);
	}
}
