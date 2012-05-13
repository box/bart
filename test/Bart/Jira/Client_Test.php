<?php
namespace Bart\Jira;

use Bart\Diesel;

class Client_Test extends \Bart\Base_Test_Case
{
	public $di;
	public $opts;
	public $soap;
	public $u = 'username';
	public $p = 'password';
	public $t = 'token';

	public function setUp()
	{
		$this->di = new Diesel();
		$this->opts = array('wsdl' => 'some random path');
		$this->soap = $this->getMock('\\SoapClient', array(), array(), '', false);
	}

	/**
	 * Register our mock soap instance with local Diesel
	 */
	private function register_soap_with_diesel()
	{
		$phpu = $this;
		$this->di->register_local('Bart\\Jira\\Client', 'SoapClient', function($params) use($phpu) {
			$phpu->assertEquals($phpu->opts, $params['options']);
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
		$this->assert_throws('Bart\\Jira\\Soap_Exception', 'Authentication failed', function() use($phpu) {
			$c = new Client($phpu->u, $phpu->p, $phpu->opts, $phpu->di);
		});
	}

	public function test_successful_login()
	{
		$this->register_soap_with_diesel();
		$this->configure_login(array());

		// Success should yield no exception
		$c = new Client($this->u, $this->p, $this->opts, $this->di);
	}

	public function test_call_to_anything()
	{
		$this->register_soap_with_diesel();
		$this->configure_login(array(
			array('getIssue', array($this->t, 'BOX-1234'), array('id' => 1234)),
			array('getIssueById', array($this->t, 'BOX-1235'), array('id' => 1234)),
		));

		$c = new Client($this->u, $this->p, $this->opts, $this->di);

		$issue = $c->getIssue('BOX-1234');
		$this->assertEquals(1234, $issue['id'], 'Client did not return issue');

		$issue = $c->getIssueById('BOX-1235');
		$this->assertEquals(1234, $issue['id'], 'Client did not return issue');
	}
}
