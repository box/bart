<?php
namespace Bart;

class Ldap
{
	private $connection = null;
	/** @var PHPLDAP */
	private $phpldap;
	private $logger;
	private $host, $port, $timeout, $auth_dn, $auth_pwd, $basedn;

	public function __construct(array $config)
	{
		$this->host = $config['server'];
		$this->port = $config['port'];
		$this->timeout = $config['timeout'];
		$this->auth_dn = $config['binduser'];
		$this->auth_pwd = $config['bindpw'];
		$this->basedn = $config['basedn'];

		$this->logger = \Logger::getLogger(__CLASS__);
		$this->phpldap = Diesel::create('Bart\PHPLDAP');
	}

	public function connect()
	{
		$this->logger->trace('LDAP Auth: Connecting to ' . $this->host);
		$conn = $this->phpldap->ldap_connect($this->host, $this->port);

		if (!$conn) {
			throw new \Exception('Could not connect to LDAP host(s) at ' . $this->host);
		}

		$this->logger->trace('Setting timeout to ' . $this->timeout . ' second(s)');
		$this->phpldap->ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, $this->timeout);

		$this->logger->trace('LDAP Auth: Binding to ' . $this->host . ' with dn ' . $this->auth_dn);
		if (!$this->phpldap->ldap_bind($conn, $this->auth_dn, $this->auth_pwd)) {
			throw new \Exception('LDAP Auth: bind unsuccessful');
		}

		$this->logger->trace('LDAP Auth: bind successful');
		$this->connection = $conn;
	}

	public function auth_user($username, $password)
	{
		if (($res_id = $this->phpldap->ldap_search($this->connection, $this->basedn, "uid=$username")) == false) {
			throw new \Exception('LDAP Auth: User ' . $username . ' not found in search');
		}

		if ($this->phpldap->ldap_count_entries($this->connection, $res_id) != 1) {
			throw new \Exception('LDAP Auth: failure, username ' . $username . 'found more than once');
		}

		if (($entry_id = $this->phpldap->ldap_first_entry($this->connection, $res_id)) == false) {
			throw new \Exception('LDAP Auth: failure, entry of search result could not be fetched');
		}

		if (($user_dn = $this->phpldap->ldap_get_dn($this->connection, $entry_id)) == false) {
			throw new \Exception('LDAP Auth: failure, user-dn could not be fetched');
		}

		// Finally, attempt to auth as user
		if (!$this->phpldap->ldap_bind($this->connection, $user_dn, $password)) {
			throw new \Exception('LDAP Auth: failure, username/password did not match for ' . $user_dn);
		}

		$this->logger->trace('LDAP Auth: Success ' . $user_dn . ' authenticated successfully');

		return new LdapUser($user_dn, $username);
	}

	public function close()
	{
		if (!$this->connection) return;

		try {
			$this->phpldap->ldap_close($this->connection);
		}
		catch (\Exception $e) {
			// Suppress any exceptions
		}
	}
}

/**
 * Little POPO class to hold ldap user info
 */
class LdapUser
{
	/** @var string Distinguished name */
	public $dn;
	/** @var string Username */
	public $name;

	public function __construct($dn, $name)
	{
		$this->dn = $dn;
		$this->name = $name;
	}
}

/**
 * Necessary wrapper for global Ldap functions
 */
class PHPLDAP
{
	public function ldap_connect($hostname, $port = null)
	{
		return ldap_connect($hostname, $port);
	}

	public function ldap_set_option($link_identifier, $option, $newval)
	{
		return ldap_set_option($link_identifier, $option, $newval);
	}

	public function ldap_bind($link_identifier, $bind_rdn = null, $bind_password = null)
	{
		return ldap_bind($link_identifier, $bind_rdn, $bind_password);
	}

	public function ldap_search ($link_identifier, $base_dn, $filter, $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null)
	{
		return ldap_search($link_identifier, $base_dn, $filter, (array)$attributes, $attrsonly, $sizelimit, $timelimit, $deref);
	}

	public function ldap_count_entries($link_identifier, $result_identifier)
	{
		return ldap_count_entries($link_identifier, $result_identifier);
	}

	public function ldap_first_entry($link_identifier, $result_identifier)
	{
		return ldap_first_entry($link_identifier, $result_identifier);
	}

	public function ldap_get_dn($link_identifier, $result_entry_identifier)
	{
		return ldap_get_dn($link_identifier, $result_entry_identifier);
	}

	public function ldap_close($link_identifier)
	{
		return ldap_close($link_identifier);
	}
}
