<?php
namespace Bart\Jira;

use Bart\Diesel;

class Client_Test extends \Bart\BaseTestCase
{
	public $opts;
	public $soap;
	public $u = 'username';
	public $p = 'password';
	public $t = 'token';

	public function setUp()
	{
		$this->opts = array('wsdl' => 'some random path', 'key' => 'val');
		$this->soap = $this->getMock('\\SoapClient', array(), array(), '', false);
		parent::setUp();
	}

	/**
	 * Register our mock soap instance with local Diesel
	 */
	private function register_soap_with_diesel()
	{
		$phpu = $this;
		Diesel::registerInstantiator('\\SoapClient',
			function($wsdl, $options) use($phpu) {
				$phpu->assertEquals($phpu->opts['wsdl'], $wsdl, 'wsdl');
				$phpu->assertEquals($phpu->opts['key'], $options['key'], 'opts');
				return $phpu->soap;
			});
	}

	/**
	 * Configure the login call to return true, and all subsequent calls as defined by valueMap
	 */
	private function configure_login(array $valueMap)
	{
		$call_count = count($valueMap) + 1;

		// Expect call to login with u & p ==> return token
		$valueMap[] = array(
			'login', array($this->u, $this->p), $this->t
		);

		$this->soap->expects($this->exactly($call_count))
			->method('__call')
			->will($this->returnValueMap($valueMap));
	}

	public function test_failed_auth()
	{
		$this->register_soap_with_diesel();
		$this->soap->expects($this->any())
			->method('__call')
			->with($this->equalTo('login'), $this->equalTo(array($this->u, $this->p)))
			->will($this->throwException(new \SoapFault('blah', 'blash')));

		$phpu = $this;
		$this->assertThrows('Bart\\Jira\\Soap_Exception', 'Authentication failed', function() use($phpu) {
			$c = new Client($phpu->u, $phpu->p, $phpu->opts);
		});
	}

	public function test_successful_login()
	{
		$this->register_soap_with_diesel();
		$this->configure_login(array());

		// Success should yield no exception
		$c = new Client($this->u, $this->p, $this->opts);
	}

	public function test_call_to_anything()
	{
		$this->register_soap_with_diesel();
		$this->configure_login(array(
			array('getIssue', array($this->t, 'BOX-1234'), array('id' => 1234)),
			array('getIssueById', array($this->t, 'BOX-1235'), array('id' => 1234)),
		));

		$c = new Client($this->u, $this->p, $this->opts);

		$issue = $c->getIssue('BOX-1234');
		$this->assertEquals(1234, $issue['id'], 'Client did not return issue');

		$issue = $c->getIssueById('BOX-1235');
		$this->assertEquals(1234, $issue['id'], 'Client did not return issue');
	}
}
