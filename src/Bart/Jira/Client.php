<?php
namespace Bart\Jira;

use Bart\Diesel;

/**
 * Small wrapper around Soap interface to jira
 */
class Client
{
	private $soap;
	private $token;

	/**
	 *
	 * @param string $usename Jira username
	 * @param string $password Jira password
	 * @param array $options Generic soap options hash. Must, at the least, specify the WSDL to
	 * your Jira SOAP, which can be a local or remote resource. It typically is available at
	 * http://$your-jira-server/rpc/soap/jirasoapservice-v2?wsdl'
	 */
	public function __construct($username, $password, $options)
	{
		$wsdl = $options['wsdl'];
		unset($options['wsdl']);
		$this->soap = Diesel::create('\\SoapClient', $wsdl, $options);

		try
		{
			$this->token = $this->soap->login($username, $password);
		}
		catch(\SoapFault $f)
		{
			throw new Soap_Exception($f, 'Authentication failed');
		}
	}

	/**
	 * See a description of the Jira API at http://docs.atlassian.com/software/jira/docs/api/rpc-jira-plugin/latest/com/atlassian/jira/rpc/soap/JiraSoapService.html
	 */
	public function __call($method, $args)
	{
		array_unshift($args, $this->token);
		try
		{
			return $this->soap->__call($method, $args);
		}
		catch(\SoapFault $f)
		{
			throw new Soap_Exception($f);
		}
	}
}

class Soap_Exception extends \Exception
{
	public function __construct(\SoapFault $f, $message = null)
	{
		$message = $message ?: $this->resolveMessage($f);
		parent::__construct($message, $f->getCode(), $f);
	}

	private function resolveMessage(\SoapFault $f)
	{
		$message = $f->getMessage();
		return $message == 'looks like we got no XML document' ?
				'Query returned empty data set' :
				$message;
	}
}
