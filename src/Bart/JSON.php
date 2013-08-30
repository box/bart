<?php
namespace Bart;

/**
 * Cohesive little bundle of PHP json knowledge
 */
class JSON
{
	public static function decode($json, $as_class = false, $depth = 512)
	{
		$array = json_decode($json, !$as_class, $depth);

		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				return $array;
			case JSON_ERROR_DEPTH:
				throw new JSONParseException('Maximum stack depth exceeded');
			case JSON_ERROR_STATE_MISMATCH:
				throw new JSONParseException('Underflow or the modes mismatch');
			case JSON_ERROR_CTRL_CHAR:
				throw new JSONParseException('Unexpected control character found');
			case JSON_ERROR_SYNTAX:
				throw new JSONParseException('Syntax error, malformed JSON');
			case JSON_ERROR_UTF8:
				throw new JSONParseException('Malformed UTF-8 characters, possibly incorrectly encoded');
			default:
				throw new JSONParseException('Unknown error');
		}
	}
}

class JSONParseException extends \Exception
{
}
