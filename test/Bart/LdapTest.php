<?php
namespace Bart;

class LdapTest extends BaseTestCase
{
	private $config = array(
			'server' => 'ldap.ve.box.net ldap.sv2.box.net',
			'port' => 'port',
			'timeout' => 'timeout',
			'binduser' => 'binduser',
			'bindpw' => 'bindpw',
			'basedn' => 'basedn',
		);
	private $brayDN = 'cn=John Braynard,ou=Engineering,ou=Employees,dc=ops,dc=box,dc=net';
	private static $ldapInstalled = false;

	public static function setUpBeforeClass()
	{
		// Load the class file, which contains some supporting classes
		Autoloader::autoload('Ldap');

		self::$ldapInstalled = function_exists('ldap_connect');
	}

	/**
	 * Ldap extensions are not available on some systems. Notably on travis-ci
	 * https://github.com/travis-ci/travis-cookbooks/pull/67
	 * @return bool Whether or not to return early
	 */
	private function skipIfNoLdap()
	{
		if (!self::$ldapInstalled) {
			$this->markTestSkipped('LDAP extensions not installed');
			return true;
		}

		return false;
	}

	public function testHappyPath()
	{
		if ($this->skipIfNoLdap()) return;

		$mock = $this->getMock('Bart\PHPLDAP');
		$mock->expects($this->exactly(2))
			->method('ldap_bind')
			->will($this->returnValueMap(array(
				array('conn', 'binduser', 'bindpw', true),
				array('conn', $this->brayDN, 'jbraynardpwd', true),
			)));
		$this->stubSearchSequence($mock);

		Diesel::registerInstantiator('Bart\PHPLDAP', function() use ($mock) { return $mock; });

		$ldap = new Ldap($this->config);
		$ldap->connect();
		$user = $ldap->auth_user('jbraynard', 'jbraynardpwd');
		$this->assertEquals($this->brayDN, $user->dn, 'User DN');
		$this->assertEquals('jbraynard', $user->name, 'User name');
	}

	public function testFailedSearch()
	{
		if ($this->skipIfNoLdap()) return;

		$mock = $this->getMock('Bart\PHPLDAP');
		$mock->expects($this->exactly(2))
			->method('ldap_bind')
			->will($this->returnValueMap(array(
				array('conn', 'binduser', 'bindpw', true),
				array('conn', $this->brayDN, 'jbraynardpwd', false),
			)));
		$this->stubSearchSequence($mock);

		Diesel::registerInstantiator('Bart\PHPLDAP', function() use ($mock) { return $mock; });

		$ldap = new Ldap($this->config);
		$ldap->connect();
		$this->assertThrows('Exception',
			"LDAP Auth: failure, username/password did not match for $this->brayDN",
			function() use ($mock, $ldap)
			{
				$ldap->auth_user('jbraynard', 'jbraynardpwd');
			});
	}

	private function stubSearchSequence($mock)
	{
		// First connect
		$mock->expects($this->once())
			->method('ldap_connect')
			->will($this->returnValue('conn'));
		// ...and then search
		$mock->expects($this->once())
			->method('ldap_search')
			->with('conn', 'basedn', 'uid=jbraynard')
			->will($this->returnValue(true));
		$mock->expects($this->once())
			->method('ldap_count_entries')
			->will($this->returnValue(1));
		$mock->expects($this->once())
			->method('ldap_first_entry')
			->will($this->returnValue(1));
		$mock->expects($this->once())
			->method('ldap_get_dn')
			->will($this->returnValue($this->brayDN));
	}
}
