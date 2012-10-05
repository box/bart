<?php
namespace Bart\Crypto;

/**
 * Contains functions for encoding/decoding strings and objects and password generations
 *
 * @author Jonathan Creasy <jcreasy@box.com>
 */
class Hash
{
	/** @var string The key used to encrypt/decrypt hashed values */
	public static $key;

	public static function configure($key)
	{
		self::$key = $key;
	}

	public static function unconfigure()
	{
		unset(self::$key);
	}

	/**
	 * Used to create an auth token array
	 * @param string $token
	 * @param string $type
	 * @return string
	 */
	public static function create_auth_array($token, $type)
	{
		$token_array = array(
			'auth_token' => $token,
			'type' => $type,
			'timestamp'=>time()
		);

		return self::encode_object($token_array);
	}

	/**
	 * Generates a password of varying length and strength.
	 * Does not include the letters 'O' or 'I' to avoid confusion.
	 *
	 * @param int $length
	 * @param int $strength
	 * @return string Password
	 */
	public static function generate_password($length = 9, $strength = 8)
	{
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';

		if ($strength & 1) {
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}

		if ($strength & 2) {
			$vowels .= "AEUY";
		}

		if ($strength & 4) {
			$consonants .= '23456789';
		}

		if ($strength & 8) {
			$consonants .= '@#$%';
		}

		$password = '';
		$alt = time() % 2;

		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} else {
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}

		return $password;
	}

	/**
	 * Converts a string to an encoded hash
	 * @param string $string
	 * @param string $key
	 * @return string encrypted string
	 */
	public static function encode_hash($string, $key=NULL)
	{
		if ($key === NULL)
		{
			$key = self::$key;
		}

		return Mcrypt::encrypt($string, NULL, NULL, $key);
	}

	/**
	 * Converts an encoded hash to the original value
	 * @param string $hash
	 * @param string $key
	 * @return string Decrypted string
	 */
	public static function decode_hash($hash, $key=NULL)
	{
		if ($key === NULL)
		{
			$key = self::$key;
		}

		return Mcrypt::decrypt($hash, NULL, NULL, $key);
	}

	/**
	 * @param mixed $object
	 * @return string Encoded hash of the JSON encoded object
	 */
	public static function encode_object($object)
	{
		$json = json_encode($object);
		return self::encode_hash($json);
	}

	/**
	 * @param string $hash
	 * @return mixed original object decrypted and json_decoded
	 */
	public static function decode_object($hash)
	{
		$json = self::decode_hash($hash);
		return json_decode($json);
	}
}
