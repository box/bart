<?php
namespace Bart\Shell;
use Bart\Log4PHP;

/**
 * Encapsulates a shell command
 */
class Command
{
	/** @var \Logger */
	private $logger;
	private $safeCommandStr;

	/**
	 * @param string $commandFormat Command string to run. Use printf like placeholders for argument
	 * WARNING Only STRINGS supported. This is due to escapeshellcommand converting everything to a string.
	 * @param string $args, ... [Optional] All arguments
	 * @warning Do NOT single-quote any arg placeholders in $commandFormat. This will be done by
	 * the class itself and placing single-quotes in the command string will negate this work.
	 */
	public function __construct($commandFormat)
	{
		$this->logger = Log4PHP::getLogger(__CLASS__);

		$safeCommandFormat = escapeshellcmd($commandFormat);

		$args = func_get_args();
		array_shift($args); // bump off the format string from the front

		$this->safeCommandStr = self::makeSafeString($safeCommandFormat, $args);
		$this->logger->debug('Set safe command string ' . $this->safeCommandStr);
	}

	public function __toString()
	{
		return "{$this->safeCommandStr}";
	}

	/**
	 * Safely format a string for use on command line.
	 * You should aim to always use {@see self} for building execution strings,
	 * but sometimes it's not possible
	 *
	 * @param string $format The sprintf-like formatted string (Note all placeholders must be strings)
	 * @param string[] $args The arguments to the formatted string
	 * @return string The put together string
	 */
	public static function makeSafeString($format, array $args)
	{
		$safeArgs = array($format);
		foreach ($args as $arg) {
			$safeArgs[] = escapeshellarg($arg);
		}

		return call_user_func_array('sprintf', $safeArgs);
	}

	/**
	 * @param bool $returnOutputAsString [Optional] By default, command output is returned as an array
	 * @return array|string Output of command
	 * @throws CommandException if command fails
	 */
	public function run($returnOutputAsString = false)
	{
		$output = array();
		$returnVar = 0;

		$this->logger->trace('Executing ' . $this->safeCommandStr);
		exec($this->safeCommandStr, $output, $returnVar);

		if ($returnVar !== 0) {
			$this->logger->error('Non-zero exit status ' . $returnVar);

			throw new CommandException("Got bad status $returnVar for {$this->safeCommandStr}. Output: "
					. implode("\n", $output));
		}

		return $returnOutputAsString ?
				implode("\n", $output) :
				$output;
	}
}

