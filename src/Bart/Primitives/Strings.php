<?php
namespace Bart\Primitives;

/**
 * Methods to help deal with PHP strings
 */
class Strings
{
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
} 