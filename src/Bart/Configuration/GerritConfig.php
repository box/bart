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
; Required. The host running the service
host = gerrit.example.com

[www]
; Valid options: https (default), http
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

[review]
; Optional, required score to be considered approved
; default 10
score = 2
; Optional, required verification score to be considered approved
; default (empty) meaning no verification is required
verified = 1

README;
	}

	public function host()
	{
		return $this->getValue('gerrit', 'host');
	}

	public function wwwScheme()
	{
		return $this->getValue('www', 'scheme', 'http');
	}

	public function wwwPort()
	{
		return $this->getValue('www', 'port', 8080);
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

	/**
	 * @return int Required review score for a review to be considered approved
	 */
	public function reviewScore()
	{
		return $this->getNumeric('review', 'score', 10);
	}

	/**
	 * @return int|null Required verification score for a review to be considered approved.
	 * null if verification not required
	 */
	public function verifiedScore()
	{
		return $this->getNumeric('review', 'verified', null);
	}
}
