<?php

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
			throw new Exception('Problem contacting ' . $url);
		}

		curl_close($ch);

		return $html;
	}

	/**
	 * @param $url {String} The URL to hit. Do NOT include get parameters in this
	 * @param $get_params {array} An associative array of get parameters
	 * @param $post_params {array} The data to send in your post
	 * @param $port {int} The port to which to post
	 *
	 * @returns {String} Response
	 */
	public static function post($url, array $get_params, array $post_params, $port = 86)
	{
		$ch = curl_init($url . '?' . http_build_query($get_params));

		// Do not return http headers
		// Do not output the contents of the call, instead return in a string
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
		curl_setopt($ch, CURLOPT_PORT, $port);

		$result = curl_exec($ch);

		if ((curl_errno($ch) != 0) || ($result === FALSE))
		{
			throw new Exception('Problem contacting ' . $url);
		}

		curl_close($ch);

		return $result;
	}
}
