<?php
namespace Bart\Primitives;
use Bart\Log4PHP;

/**
 * Command interactions with Numbers
 */
class Numbers
{
	/** @var \Logger Do not access this directly, use self::logger() */
	private static $_logger;

	/**
	 * @return \Logger
	 */
	private static function logger()
	{
		if (!self::$_logger) {
			self::$_logger = Log4PHP::getLogger(__CLASS__);
		}

		return self::$_logger;
	}

	/**
	 * Attempt to convert a string to a number
	 * @param mixed $str
	 * @return int|string
	 * @throws PrimitivesException
	 */
	public static function cast_to_numeric($str)
	{
		$str = trim($str);

		if (ctype_digit($str)) {
			return intval($str);
		}

		if (is_numeric($str)) {
			return $str;
		}

		self::logger()->warn("Could not coerce '$str' into number");
		throw new PrimitivesException('Could not coerce string into number');
	}
}

