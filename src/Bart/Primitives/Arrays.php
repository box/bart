<?php
namespace Bart\Primitives;

/**
 * Common array interactions
 */
class Arrays
{
	/**
	 * @static Value or default
	 * @param array $array
	 * @param mixed $key
	 * @param mixed $default [Optional] Default to use if no value exists at key
	 * @return mixed Value for $key or the default
	 */
	public static function vod(array $array, $key, $default = null)
	{
		if (array_key_exists($key, $array))
		{
			return $array[$key];
		}

		return $default;
	}

	/**
	 * @static Value or fail
	 * @param array $array
	 * @param mixed $key
	 * @return mixed
	 * @throws PrimitivesException
	 */
	public static function vof(array $array, $key)
	{
		if (array_key_exists($key, $array))
		{
			return $array[$key];
		}

		throw new PrimitivesException("No such key in array for key: $key");
	}

	/**
	 * @deprecated @see Arrays::hashToS()
	 */
	public static function hash_to_s(array $hash)
	{
		return self::hashToS($hash);
	}

	/**
	 * @param array $hash Key value pairs
	 * @return string The hash as one long string
	 */
	public static function hashToS(array $hash)
	{
		$array = array();
		foreach ($hash as $k => $v)
		{
			$array[] = '{' . $k . '}=>{' . $v . '}';
		}

		return implode(', ', $array);
	}
}
