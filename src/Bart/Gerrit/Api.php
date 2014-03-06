<?php
namespace Bart\Gerrit;

use Bart\Configuration\GerritConfig;
use Bart\Diesel;
use Bart\JSON;
use Bart\JSONParseException;
use Bart\Log4PHP;
use Bart\Shell\Command;
use Bart\Shell\CommandException;

/**
 * Wrapper for the Gerrit API
 */
class Api
{
	/** @var \Bart\SshWrapper */
	private $ssh;
	/** @var \Bart\Configuration\GerritConfig */
	private $config;
	/** @var \Logger */
	private $logger;

	/**
	 * @param array $conf Configurations for reaching Gerrit server
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
		$this->logger->trace("Configured Gerrit API using ssh {$ssh}");
	}

	/**
	 * @deprecated Use {@see \Bart\Gerrit\Change}
	 * Query gerrit for an approved change
	 * @param string $changeId Gerrit Change-Id
	 * @param string $commitHash Latest commit hash pushed to gerrit
	 */
	public function getApprovedChange($changeId, $commitHash)
	{
		$reviewScore = $this->config->reviewScore();
		$verifiedScore = $this->config->verifiedScore();

		$verifiedOption = '';
		if ($verifiedScore !== null) {
			$verifiedOption = ",Verified={$verifiedScore}";
		}

		return $this->getChange($changeId, array(
			'commit' => $commitHash,
			'label'=> "CodeReview={$reviewScore}{$verifiedOption}",
		));
	}

	/**
	 * Review a patchset
	 * @param string $unique The commit hash or the change_num,patch_set_id
	 * @param string $score E.g. +2
	 * @param string $comment Comment to leave with review
	 * @param string $options Additional options. See
	 * https://gerrit-review.googlesource.com/Documentation/cmd-review.html
	 * @return void
	 */
	public function review($unique, $score, $comment, $options = '')
	{
		// This gets injected as a command argument, which will itself get escaped
		// ...so, it needs to be double escaped
		$escapedComment = escapeshellarg($comment);

		$reviewScore = $score != null ? "--code-review $score" : '';
		$message = "--message $escapedComment";

		$reviewCmd = "gerrit review $options $reviewScore $message $unique";

		$this->send($reviewCmd, false);
	}

	/**
	 * @param string $query
	 * @param string[] $args
	 * @return ApiResult
	 */
	public function query($query, array $args)
	{
		$safeQuery = Command::makeSafeString("gerrit query --format=JSON $query" , $args);

		return $this->send($safeQuery);
	}

	/**
	 * @param string $gsql
	 * @param string[] $args
	 * @return ApiResult
	 */
	public function gsql($gsql, array $args)
	{
		$safeGsql = Command::makeSafeString($gsql, $args);

		return $this->send("gerrit gsql --format=JSON -c \"$safeGsql\"");
	}

	/**
	 * @deprecated Use {@see \Bart\Gerrit\Change}
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

		$result = $this->send($remoteQuery);
		$records = $result->rawRecords();

		// NOTE can probably use :stats here
		if (count($records) > 1) {
			throw new GerritException('More than one gerrit record matched');
		}

		if (count($records) == 0) {
			return null;
		}

		return $records[0];
	}

	/**
	 * @deprecated Build these by hand for now and use generic :gsql and :query methods
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
	 * @param boolean $expectResponse If a JSON response is expected
	 * @return null|ApiResult
	 * @throws GerritException
	 */
	private function send($remoteQuery, $expectResponse = true)
	{
		try {
			$this->logger->debug("Sending remote command to gerrit: $remoteQuery");
			$gerritResponseArray = $this->ssh->exec($remoteQuery);

			if (!$expectResponse) {
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

			return new ApiResult($stats, $records);
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
