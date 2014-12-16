<?php
namespace Bart\Gerrit;
use Bart\Diesel;
use Bart\Log4PHP;

/**
 * Please provide a concise description.
 */
abstract class GerritClientBase
{
	/** @var \Bart\SshWrapper */
	protected $ssh;
	/** @var \Bart\Configuration\GerritConfig */
	protected $config;
	/** @var \Logger */
	protected $logger;

	/**
	 * Create wrapper for Gerrit Administrative API
	 */
	public function __construct()
	{
		/** @var \Bart\Configuration\GerritConfig $config */
		$config = Diesel::create('Bart\Configuration\GerritConfig');

		/** @var \Bart\SshWrapper $ssh */
		$ssh = Diesel::create('Bart\SshWrapper', $config->host(), $config->sshPort());
		$ssh->setCredentials($config->sshUser(), $config->sshKeyFile());

		$this->ssh = $ssh;
		$this->config = $config;

		$this->logger = Log4PHP::getLogger(__CLASS__);
		$this->logger->trace("Configured Gerrit Administrative API client using ssh {$ssh}");
	}
}