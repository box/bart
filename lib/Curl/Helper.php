<?php

/**
 * @deprecated Use the Curl.php class
 */
class Curl_Helper
{
	// Hackish way to avoid passing around Curl_Helper instances
	// ...but still allow stubs/mocks for unit testing
	public static $cache = array();

	/**
	 * Curl $url as GET request
	 *
	 * @param $params Params to be sent with GET request
	 * @returns HTML source of resource url
	 */
	public static function get($url, array $params)
	{
		if (isset(self::$cache[$url])) return self::$cache[$url];

		$ch = curl_init($url . '?' . http_build_query($params));

		// Do not return http headers
		// Do not output the contents of the call, instead return in a string
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$html = curl_exec($ch);

		if ((curl_errno($ch) != 0) || ($html === FALSE))
		{
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception("GET Error for $url, curl error: " . $error);
		}

		curl_close($ch);

		return $html;
	}

	/**
	 * @param $url The URL to hit. Do NOT include get parameters in this
	 * @param $get_params An associative array of get parameters
	 * @param $post_params The data to send in your post
	 * @param $port The post port
	 *
	 * @return Remote response body as string
	 */
	public static function post($url, array $get_params, array $post_params, $port)
	{
		$ch = curl_init($url . '?' . http_build_query($get_params));

		// Do not return http headers
		// Do not output the contents of the call, instead return in a string
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
		curl_setopt($ch, CURLOPT_PORT, $port);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($ch);

		if ((curl_errno($ch) != 0) || ($result === FALSE))
		{
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception("Error posting to $url, curl error: " . $error);
		}

		curl_close($ch);

		return $result;
	}
}

