<?php
namespace Bart\Shell;

/**
 * Result of running a command
 */
class CommandResult
{
	/** @var \Bart\Shell\Command */
	private $cmd;
	/** @var array Output from exec() */
	private $output;
	/** @var int Shell return status */
	private $statusCode;

	/**
	 * @param Command $cmd The command object that was run
	 * @param array $output The output of exec
	 * @param int $statusCode The shell return status
	 */
	public function __construct(Command $cmd, $output, $statusCode)
	{
		$this->cmd = $cmd;
		$this->output = $output;
		$this->statusCode = $statusCode;
	}

	public function wasOk()
	{
		return $this->statusCode === 0;
	}

	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * @param bool $asString Implode output to single string
	 */
	public function getOutput($asString = false)
	{
		return $asString ?
			implode("\n", $this->output) :
			$this->output;
	}
}
