<?php
namespace Bart\GitHook;

use Bart\Diesel;
use Bart\Git\Commit;

/**
 * Enforces that a commit is approved in gerrit
 */
class GerritApproved extends GitHookAction
{
	/** @var \Bart\Gerrit\Api */
	private $api;

	public function __construct()
	{
		parent::__construct();

		/** @var \Bart\Gerrit\Api api */
		$this->api = Diesel::create('Bart\Gerrit\Api');
	}

	/**
	 * Fails if review is not approved in Gerrit
	 * @param Commit $commit
	 * @throws GitHookException
	 */
	public function run(Commit $commit)
	{
		// Let exception bubble up if no change id
		$changeId = $commit->gerritChangeId();

		$data = null;
		try
		{
			$this->logger->debug("Getting data from gerrit: {$changeId}");
			$data = $this->api->getApprovedChange($changeId, $commit->revision());
		}
		catch(\Exception $e)
		{
			throw new GitHookException("Error getting Gerrit review info for {$changeId}", $e->getCode(), $e);
		}

		if ($data == null)
		{
			throw new GitHookException ('An approved review was not found in Gerrit for'
					. " commit {$commit} with Change-Id {$changeId}");
		}

		$this->logger->info('Gerrit approved.');
	}
}
