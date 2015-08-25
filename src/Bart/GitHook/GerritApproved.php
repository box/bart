<?php
namespace Bart\GitHook;

use Bart\Diesel;
use Bart\Gerrit\Change;
use Bart\Git\Commit;
use Bart\Git\GitException;

/**
 * Enforces that a commit is approved in gerrit
 */
class GerritApproved extends GitHookAction
{
	/**
	 * Fails if review is not approved & verified in Gerrit
	 * @param Commit $commit A git commit with a Change-Id
	 * @throws GitHookException If Change-Id not found or the review is not approved or verified
	 */
	public function run(Commit $commit)
	{
		try {
			$changeId = $commit->gerritChangeId();
		} catch (GitException $e) {
			$this->logger->warn("{$e->getMessage()}. Skipping commit.");
			throw new GitHookException("Couldn't get Change-Id for {$commit}", $e->getCode(), $e);
		}

		/** @var \Bart\Gerrit\Change $change */
		$change = Diesel::create('\Bart\Gerrit\Change', $changeId);

		if (!$change->isReviewedAndVerified()) {
			$msg = "Could not find an approved & verified change in Gerrit for change $changeId in commit $commit";
			$this->logger->info($msg);

			throw new GitHookException($msg);
		}

		$this->logger->info('Gerrit approved.');
	}
}
