<?php
namespace Bart\Shell;

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
		$this->logger = \Logger::getLogger(__CLASS__);

		$safeCommandFormat = escapeshellcmd($commandFormat);

		$args = func_get_args();
		array_shift($args);

		$safeArgs = array($safeCommandFormat);
		foreach ($args as $arg) {
			$safeArgs[] = escapeshellarg($arg);
		}

		$this->safeCommandStr = call_user_func_array('sprintf', $safeArgs);
		$this->logger->debug('Set safe command string ' . $this->safeCommandStr);
	}

	public function __toString()
	{
		return "{$this->safeCommandStr}";
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

