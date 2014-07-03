<?php
namespace Bart;

/**
 * Wrapper around command line SSH
 */
class SshWrapper
{
	private $options = array("UserKnownHostsFile=/dev/null", "StrictHostKeyChecking=no");

	private $host, $port;
	private $user, $keyFile;

	/**
	 * @param string $host
	 * @param int $port
	 */
	public function __construct($host, $port = 22)
	{
		$this->host = $host;
		$this->port = $port;
	}

	public function setCredentials($user, $keyFile)
	{
		$this->user = $user;
		$this->keyFile = $keyFile;
	}

	public function __toString()
	{
		$user = ($this->user) ? $this->user : '';
		return "{$user}@{$this->host}:{$this->port}";
	}

	/**
	 * @param array $options Literal ssh command line options (must be supported by -o)
	 */
	public function setOptions(array $options)
	{
		$this->options = array_merge($options, $this->options);
	}

	/**
	 * Shortcut to @see self::createShellCommand. Execute the command remotely and return the result
	 * ...or raise an exception on a non-zero return code
	 * @param string $remoteCommand Command to run on remote host
	 * @param bool $returnOutputAsString [Optional] By default, command output is returned as an array
	 * @return array Output of ssh command
	 */
	public function exec($remoteCommand, $returnOutputAsString = false)
	{
		$cmd = $this->createShellCommand($remoteCommand);

		return $cmd->run($returnOutputAsString);
	}

	/**
	 * Create a Command object that will execute the SSH command
	 * @param string $remoteCommand Command to run on remote server
	 * @return Shell\Command Command instance configured to establish SSH
	 * connection and execute the remote command
	 */
	public function createShellCommand($remoteCommand)
	{
		$sshCommandStem = 'ssh %s -q -p %s';
		$args = array($this->host, $this->port);

		foreach ($this->options as $option) {
			$sshCommandStem .= ' -o %s';
			$args[] = $option;
		}

		if ($this->user) {
			$sshCommandStem .= ' -l %s';
			$args[] = $this->user;
		}

		if ($this->keyFile) {
			$sshCommandStem .= ' -i %s';
			$args[] = $this->keyFile;
		}

		$sshCommandStem .= ' %s';
		$args[] = $remoteCommand;

		// Put all the args into one array
		array_unshift($args, $sshCommandStem);

		/** @var \Bart\Shell $shell */
		$shell = Diesel::create('Bart\Shell');

		/** @var \Bart\Shell\Command $cmd */
		return call_user_func_array(array($shell, 'command'), $args);
	}
}
