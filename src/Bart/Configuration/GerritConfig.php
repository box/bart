<?php
namespace Bart\Configuration;

/**
 * Configurations for Gerrit and related classes
 */
class GerritConfig extends Configuration
{
	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public function README()
	{
		return <<<README
[gerrit]
; Required, the host running the service
host = gerrit.example.com

[www]
; valid options: https (default), http
scheme = http
; default (empty)
port = 8080

[ssh]
; Defaults to 29418
port = 29418
; User and key file to use for ssh connections to the Gerrit server
; Generally, this user and key file should be managed via a tool like Puppet
user = gerrit
key_file = "/home/gerrit/.ssh/id_rsa"

README;
	}

	public function host()
	{
		return $this->getValue('gerrit', 'host');
	}

	public function sshPort()
	{
		return $this->getNumeric('ssh', 'port', 29418);
	}

	public function sshUser()
	{
		return $this->getValue('ssh', 'user');
	}

	public function sshKeyFile()
	{
		return $this->getValue('ssh', 'key_file');
	}
}
