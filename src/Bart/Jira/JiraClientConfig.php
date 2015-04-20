<?php
namespace Bart\Jira;

use \Bart\Configuration\Configuration;

/**
 * Configurations for using the Jira API
 */
class JiraClientConfig extends Configuration
{
	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public function README()
	{
		return <<<README
[api]
host = jira.inside-box.net
use_ssl = true
user = automator
password = plain-text-password

README;
	}

	/**
	 * @return bool
	 */
	public function useSsl()
	{
		return $this->getBool('api', 'use_ssl');
	}

	public function host()
	{
		return $this->getValue('api', 'host');
	}

	/**
	 * @return string The base URI to connect to the JIRA API
	 */
	public function baseURL()
	{
		$protocol = $this->useSsl() ? 'https' : 'http';
		return "{$protocol}://{$this->host()}";
	}

	public function username()
	{
		return $this->getValue('api', 'user');
	}

	public function password()
	{
		return $this->getValue('api', 'password');
	}
}
