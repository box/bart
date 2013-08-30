<?php
namespace Bart\Gerrit;

use Bart\Configuration\GerritConfig;
use Bart\Diesel;
use Bart\JSON;
use Bart\JSONParseException;
use Bart\Shell\CommandException;

/**
 * Wrapper for the Gerrit API
 */
class Api
{
	/** @var \Bart\SshWrapper */
	private $ssh;
	/** @var \Logger */
	private $logger;

	/**
	 * @param array $conf Configurations for reaching Gerrit server
	 */
	public function __construct()
	{
		/** @var \Bart\Configuration\GerritConfigs $config */
		$config = Diesel::create('Bart\Configuration\GerritConfigs');

		/** @var \Bart\SshWrapper $ssh */
		$ssh = Diesel::create('Bart\SshWrapper', $config->host(), $config->sshPort());
		$ssh->setCredentials($config->sshUser(), $config->sshKeyFile());

		$this->ssh = $ssh;

		$this->logger = \Logger::getLogger(__CLASS__);
		$this->logger->trace("Configured Gerrit API using ssh {$ssh}");
	}

	/**
	 * Query gerrit for an approved change
	 * @param string $change_id Gerrit Change-Id
	 * @param string $commit_hash Latest commit hash pushed to gerrit
	 */
	public function getApprovedChange($change_id, $commit_hash)
	{
		return $this->getChange($change_id, array(
			'commit' => $commit_hash,
			'label'=> 'CodeReview=10',
		));
	}

	/**
	 * Review a patchset
	 * @param string $commitHash Gerrit uses this to find patch set
	 * @param string $score E.g. +2
	 * @param string $comment Comment to leave with review
	 */
	public function review($commitHash, $score, $comment)
	{
		$score = "--code-review $score";

		$query = "gerrit review $score --message '$comment' $commitHash";

		$this->send($query);
	}

	/**
	 * @param array $filters e.g. array(
	 * 	'commit' => '$commit_hash',
	 *  'label' => 'CodeReview=10',
	 * )
	 * See http://scm.dev.box.net:8080/Documentation/user-search.html
	 * @return array Decoded json of change, or null if none matching
	 */
	private function getChange($changeId, array $filters)
	{
		$filterStr = self::makeFilterString($filters);

		$remoteQuery = 'gerrit query --format=JSON ' . $changeId . $filterStr;

		$records = $this->send($remoteQuery, true);

		if (count($records) > 1) {
			throw new GerritException('More than one gerrit record matched');
		}

		if (count($records) == 0) {
			return null;
		}

		return $records[0];
	}

	/**
	 * @return string All the filters as a string
	 */
	private static function makeFilterString(array $filters)
	{
		$str = '';
		foreach($filters as $name => $filter)
		{
			$str .= " $name:$filter";
		}

		return $str;
	}

	/**
	 * @param string $remoteQuery Gerrity query
	 * @param boolean $processResponse If a JSON response is expected
	 * @return mixed Array of json decoded results, or nil
	 * @throws GerritException
	 */
	private function send($remoteQuery, $processResponse = false)
	{
		try {
			$this->logger->debug("Calling gerrit with: $remoteQuery");
			$gerritResponseArray = $this->ssh->exec($remoteQuery);

			if (!$processResponse) {
				return;
			}

			$stats = JSON::decode(array_pop($gerritResponseArray));

			if ($stats['type'] == 'error') {
				throw new GerritException($stats['message']);
			}

			$records = array();
			foreach ($gerritResponseArray as $json) {
				$records[] = JSON::decode($json);
			}

			return $records;
		}
		catch (CommandException $e) {
			$this->logger->warn('Gerrit query failed', $e);
			throw new GerritException('Query to gerrit failed', 0, $e);
		}
		catch (JSONParseException $e) {
			$this->logger->warn('Gerrit query returned bad json', $e);
			throw new GerritException('Gerrit query returned bad json', 0, $e);
		}
	}
}

class GerritException extends \Exception
{
}
