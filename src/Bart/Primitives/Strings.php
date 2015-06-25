<?php
namespace Bart\Primitives;

/**
 * Methods to help deal with PHP strings
 */
class Strings
{

    /**
     * This constant defines an empty string, for use as reference or validation.
     */
    const EMPTY_STRING = '';

	/**
	 * Capitalize every word in $subject. Words are delimited by $delimited, which defaults to "-"
	 * If your words are separated by white space, you can just use @see ucwords()
	 * @param $subject
	 * @param string $delimiter Literal string by which the words are separated
	 * @param string $replacement Replace delimiter with the this string instead
	 * @return string
	 */
	public static function titleize($subject, $delimiter = '-', $replacement = null)
	{
		$replacement = $replacement ?: $delimiter;

		$ucs = [];
		$words = explode($delimiter, $subject);
		foreach ($words as $word)
		{
			$ucs[] = ucfirst($word);
		}

		return implode($replacement, $ucs);
	}

	/**
	 * Ensure that text is no longer than $maxLength. If so, truncate $subject at word boundary
	 * closest before $maxLength and replace with $suffix
	 * @param string $subject Original text to summarize
	 * @param int $maxLength
	 * @param string $suffix Text to append at end if $subject is longer than $maxLength,
	 * @return string Truncated text followed by $suffix if necessary
	 */
	public static function summarize($subject, $maxLength, $suffix = '...')
	{
		if (!$subject) {
			return '';
		}

		if (strlen($subject) <= $maxLength) {
			return $subject;
		}

		$suffixLen = strlen($suffix);
		// Leave space for the suffix
		$index = $maxLength - $suffixLen;
		// Look for a natural break after which to append the suffix
		while ($index > 0) {
			if (ctype_space($subject[$index])) {
				break;
			}

			$index -= 1;
		}

		// Maybe it's just one very long word
		// NOTE not covering case where $maxLength <= $suffixLen
		if ($index <= 0) {
			return substr($subject, 0, $maxLength - $suffixLen) . $suffix;
		}

		return substr($subject, 0, $index) . $suffix;
	}

    /**
     * Determine if $fullString starts with $subString (case-sensitive).
     * If a passed in string is null, it is treated as an empty string.
     * Reference: http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
     *
     * NOTE: This method assumes that an empty string ('') contains an empty string. This is an ongoing discussion with
     * apparently no right answer: http://stackoverflow.com/questions/17997204/does-an-empty-string-contain-an-empty-string-in-c
     *
     * @param string $fullString
     * @param string $subString
     * @return bool If $fullString starts with $subString, return true, else, return false.
     */
    public static function startsWith($fullString, $subString)
    {
        $fullString = self::stringOrEmpty($fullString);
        $subString = self::stringOrEmpty($subString);

        $length = strlen($subString);
        if ($length === 0) {
            return true;
        }

        return (substr($fullString, 0, $length) === $subString);
    }

    /**
     * Determine if $fullString ends with $subString (case-sensitive).
     * If a passed in string is null, it is treated as an empty string.
     * Reference: http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
     *
     * NOTE: This method assumes that an empty string ('') contains an empty string. This is an ongoing discussion with
     * apparently no right answer: http://stackoverflow.com/questions/17997204/does-an-empty-string-contain-an-empty-string-in-c
     *
     * @param string $fullString
     * @param string $subString
     * @return bool If $fullString ends with $subString, return true, else, return false.
     */
     public static function endsWith($fullString, $subString)
     {
         $fullString = self::stringOrEmpty($fullString);
         $subString = self::stringOrEmpty($subString);

         $length = strlen($subString);
         if ($length === 0) {
             return true;
         }

         return (substr($fullString, -$length) === $subString);
     }

    /**
     * Returns true if the given $string is null or is the empty string.
     * @param string $string A string reference to check
     * @return bool If the $string is null or is the empty string
     */
    public static function isNullOrEmpty($string)
    {
        return ($string === null || $string === Strings::EMPTY_STRING);
    }


    /**
     * Checks to see if string is null or empty, and if it is, returns an empty string. If the string is not null or
     * empty, it returns back the original passed in $string.
     * @param string $string A string reference to check
     * @return string If $string is empty or null, the empty string. Else, the original $string.
     */
    private static function stringOrEmpty($string)
    {
        if (Strings::isNullOrEmpty($string)) {
            return Strings::EMPTY_STRING;
        } else {
            return $string;
        }
    }

} 