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

} 