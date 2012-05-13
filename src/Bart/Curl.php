<?php
namespace Bart;

/**
 * Make curl requests
 */
class Curl
{
	private $url;
	private $port;

	/**
	 * Curl to $host_url on $port
	 */
	public function __construct($host_url, $port = 80)
	{
		$this->url = $host_url;
		$this->port = $port;
	}

	public function get($path, array $get_params, array $headers = null, $cookies = null)
	{
		return $this->request(null, $this->url . $path,
			$get_params, null, $headers, $cookies);
	}

	/**
	 * @param string $path relative path from base url
	 * @param array $get_params An associative array of get parameters
	 * @param [array,string] $post_params The data to send in your post
	 *
	 * @return Remote response body as string
	 */
	public function post($path, array $get_params, $post_params)
	{
		return $this->request(CURLOPT_POST, $this->url . $path,
			$get_params, $post_params);
	}

	/**
	 * PUT a json body
	 *
	 * @param $path relative path from base url
	 * @param $get_params An associative array of get parameters
	 * @param $body Optional request body data to send
	 *
	 * @return Remote response body as string
	 */
	public function put($path, array $get_params, $body = null)
	{
		return $this->request(CURLOPT_PUT, $this->url . $path,
			$get_params, $body, array('Content-type: application/json'));
	}

	private function request($http_method, $url, array $get_params, $body, array $headers = null, $cookies = null)
	{
		$ch = curl_init($url . '?' . http_build_query($get_params));
		curl_setopt($ch, CURLOPT_PORT, $this->port);

		if ($headers != null)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		if ($cookies)
		{
			curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		}

		if (isset($http_method))
		{
			// Hey, guess what? Curl option PUT won't work!
			// http://stackoverflow.com/questions/5043525/php-curl-http-put
			if ($http_method == CURLOPT_PUT)
			{
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			}
			else
			{
				curl_setopt($ch, $http_method, true);
			}

			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		// Do not return http headers
		curl_setopt($ch, CURLOPT_HEADER, false);
		// Do not output the contents of the call, instead return in a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($ch);

		if ((curl_errno($ch) != 0) || ($result === FALSE))
		{
			$error = curl_error($ch);
			curl_close($ch);
			throw new \Exception("Error posting to $url, curl error: $error");
		}

		curl_close($ch);

		return $result;
	}
}
