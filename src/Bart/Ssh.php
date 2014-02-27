<?php
namespace Bart;

use Bart\Diesel;

/**
 * Ssh wrapper, shells out to command line
 * @deprecated See \Bart\SshWrapper
 */
class Ssh
{
	private $server;
	private $port = 22;
	// Default params to avoid strict host key checking
	private $ssh_options = array("UserKnownHostsFile=/dev/null", "StrictHostKeyChecking=no");
	private $ssh_keyfile = null;
	private $ssh_user = null;
	private $shell;
	private $conf;

	public function __construct($server)
	{
		if (!is_string($server))
		{
			throw new \Exception("Invalid server $server");
		}

		$this->server = $server;
		$this->ssh_user = get_current_user();

		$this->shell = Diesel::create('Bart\Shell');
		$this->conf = Diesel::create('Bart\Config_Parser');
	}

	/**
	 * Use a pre-configured user and key file
	 * ./etc/php/ssh.conf
	 */
	public function use_auto_user()
	{
		$ssh_conf = $this->conf->parse_conf_file(BART_DIR . 'etc/php/ssh.conf');

		$this->ssh_user = $ssh_conf['connection']['user'];
		$this->ssh_keyfile = $ssh_conf['connection']['key_file_location'];
	}

	public function set_port($port)
	{
		$this->port = $port;
	}

	public function set_options(array $options)
	{
		$this->ssh_options = array_merge($options, $this->ssh_options);
	}

	/**
	 * Run the ssh command
	 * @param string $cmd The ssh command to execute
	 * @return array(output, exit_status)
	 */
	public function execute($cmd)
	{
		$options_string = ' -o ' . implode(' -o ', $this->ssh_options);

		$ssh_autologin_key_string = isset($this->ssh_keyfile) ? " -i $this->ssh_keyfile " : '';

		$final_cmd = "ssh -q -p $this->port $ssh_autologin_key_string $options_string"
				. " $this->ssh_user@$this->server $cmd 2>&1";

		$this->shell->exec($final_cmd, $output, $exit_status);

		return array('output' => $output, 'exit_status' => $exit_status);
	}
}
