<?php
namespace Bart\Git_Hook;

use Bart\Diesel;

/**
 * Enforces that a commit is approved in gerrit
 */
class Gerrit_Approved extends GitHookAction
{
	/** @var \Bart\Gerrit\Api */
	private $api;

	public function __construct(array $conf, $gitDir, $repo)
	{
		$gerrit_conf = $conf['gerrit'];
		parent::__construct($gerrit_conf, $gitDir, $repo);

		/** @var \Bart\Gerrit\Api api */
		$this->api = Diesel::create('Bart\Gerrit\Api');
	}

	public function run($commitHash)
	{
		// Let exception bubble up if no change id
		$change_id = $this->git->get_change_id($commitHash);

		$data = null;
		try
		{
			$this->logger->debug('Getting data from gerrit: ' . $change_id);
			$data = $this->api->getApprovedChange($change_id, $commitHash);
		}
		catch(\Exception $e)
		{
			throw new GitHookException('Error getting Gerrit review info', $e->getCode(), $e);
		}

		if ($data == null)
		{
			throw new GitHookException ('An approved review was not found in Gerrit for'
					. " commit $commitHash with Change-Id $change_id");
		}

		$this->logger->info('Gerrit approved.');
	}
}
