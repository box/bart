<?php
namespace Bart\Gerrit;
use Bart\Diesel;
use Bart\Log4PHP;
use Bart\Shell\CommandException;

/**
 * Wrap the administrative Gerrit API
 */
class GerritAdminClient extends GerritClientBase
{
	/**
	 * Requests a garbage collection of the named project
	 * This can take a while to run
	 * @param string $projectName Name of the project to garbage collect
	 * @returns string The garbage collection output
	 */
	public function gc($projectName)
	{
		try {
			$this->logger->debug("Running garbage collection on $projectName");
			$output = $this->ssh->exec("gerrit 'gerrit gc' $projectName", true);
		} catch (CommandException $e) {
			$this->logger->warn("Failed to run garbage collection on $projectName", $e);
			throw new GerritException("Garbage collection for $projectName failed", $e->getCode(), $e);
		}

		return $output;
	}
}
