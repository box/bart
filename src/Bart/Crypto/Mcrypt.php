<?php
namespace Bart\Crypto;

/**
 * Cryptography Class
 * Install mcrypt for php on mac:
 *    http://www.glenscott.co.uk/blog/2011/02/03/install-mcrypt-php-extension-on-os-x-snow-leopard/
 *    http://remonpel.nl/2012/01/adding-mcrypt-to-your-osx-based-php-server-setup/
 *
 * @author Jonathan Creasy <jonathan@ghostlab.net>
*/
class Mcrypt
{
	const PRE = 0;
	const POST = 1;

	private static $salt, $mcrypt_key;
	private static $mcrypt_cipher = MCRYPT_3DES;
	private static $mcrypt_mode   = MCRYPT_MODE_ECB;

	private function __construct() {}

	/**
	 * @param string $salt
	 * @param string $mycrypt_key
	 */
	public static function configure($salt, $mycrypt_key)
	{
		self::$salt = $salt;
		self::$mcrypt_key = $mycrypt_key;
	}

	public static function unconfigure()
	{
		unset(self::$salt);
		unset(self::$mcrypt_key);
	}

	public static function generate_app_key($app_domain, $app_name)
	{
		return abs(crc32($app_domain . $app_name . self::$salt));
	}

	public static function generate_app_pw($app_key, $remote_ip)
	{
		return md5($app_key . $remote_ip);
	}

	public static function encrypt($data, $cipher = null, $mode = null, $key = null)
	{
		if ($cipher === null)
		{
			$cipher = self::$mcrypt_cipher;
		}

		if ($key === null)
		{
			$key = self::$mcrypt_key;
		}

		if ($mode === null)
		{
			$mode = self::$mcrypt_mode;
		}
		$iv = self::get_iv($cipher, $mode);

		return self::base64url_encode(mcrypt_encrypt($cipher, $key, $data, $mode, $iv));
	}

	public static function decrypt($data, $cipher = null, $mode = null, $key = null)
	{
		if ($cipher === null)
		{
			$cipher = self::$mcrypt_cipher;
		}

		if ($key === null)
		{
			$key = self::$mcrypt_key;
		}

		if ($mode === null)
		{
			$mode = self::$mcrypt_mode;
		}

		$iv = self::get_iv($cipher, $mode);

		return rtrim(mcrypt_decrypt($cipher, $key, self::base64url_decode($data), $mode, $iv), "\0");
	}

	private static function get_iv($cipher, $mode)
	{
		$iv_size = mcrypt_get_iv_size($cipher, $mode);

		return mcrypt_create_iv($iv_size, MCRYPT_RAND);
	}

	private static function base64url_encode($plainText)
	{
		$base64 = base64_encode($plainText);
		$base64url = strtr($base64, '+/=', '-_*');
		return ($base64url);
	}

	private static function base64url_decode($base64url)
	{
		$base64 = strtr($base64url, '-_*', '+/=');
		$plainText = base64_decode($base64);
		return ($plainText);
	}

	public static function add_padding($value, $length = 0, $position = null)
	{
		if ($length <= 0)
		{
			return $value;
		}

		if ($position === null)
		{
			$position = self::PRE;
		}

		$padding = self::generate_padding($length);

		if ($position === self::PRE)
		{
			return $padding . $value;
		}

		return $value . $padding;
	}

	public static function remove_padding($value, $length = 0, $position = null)
	{
		if ($length <= 0)
		{
			return $value;
		}

		if ($position === null)
		{
			$position = self::PRE;
		}

		if ($position === self::PRE)
		{
			return substr($value, $length);
		}

		return substr($value, 0, -$length);
	}

	private static function generate_padding($length)
	{
		$pad = '';

		do
		{
			// generate random [0-9][a-z]
			$pad .= md5(((string) mt_rand()) . ((string) mt_rand()));

			$needed = $length - strlen($pad);
		}
		while ($needed > 0);

		// trim it down if necessary
		if (strlen($pad) > $length)
		{
			$pad = substr($pad, 0, $length);
		}

		$uc_cnt = mt_rand(0, $length);

		// add some [A-Z]
		for ($i=0; $i < $uc_cnt; $i++)
		{
			$idx = mt_rand(0, strlen($pad)-1);

			$pad[$idx] = strtoupper($pad[$idx]);
		}

		return $pad;
	}
}
