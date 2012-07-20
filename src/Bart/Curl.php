<?php
namespace Bart;

/**
 * Make curl requests
 */
class Curl
{
	private $uri;
	private $port;

	/**
	 * Curl to $hostUri on $port
	 */
	public function __construct($hostUri, $port = 80)
	{
		$this->uri = $hostUri;
		$this->port = $port;
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
	 * @return Remote response body as string
	 */
	public function post($path, array $getParams, $postData)
	{
		return $this->request(CURLOPT_POST, $this->uri . $path,
			$getParams, $postData);
	}

	/**
	 * PUT a json body
	 *
	 * @param $path relative path from base uri
	 * @param $getParams An associative array of get parameters
	 * @param $body Optional request body data to send
	 *
	 * @return Remote response body as string
	 */
	public function put($path, array $getParams, $body = null)
	{
		return $this->request(CURLOPT_PUT, $this->uri . $path,
			$getParams, $body, array('Content-type: application/json'));
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

		// Do not return http headers
		curl_setopt($ch, CURLOPT_HEADER, false);
		// Do not output the contents of the call, instead return in a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

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
