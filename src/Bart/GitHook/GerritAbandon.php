<?php
namespace Bart\GitHook;
use Bart\Gerrit\Change;
use Bart\Gerrit\GerritException;
use Bart\Git\Commit;
use Bart\Git\GitException;

/**
 * Abandon Gerrit review
 */
class GerritAbandon extends GitHookAction
{
	/**
	 * Run the hook
	 * @param Commit $commit Commit with Gerrit Change-Id
	 * @throws GitHookException if requirement fails
	 */
	public function run(Commit $commit)
	{
		try {
			$changeId = $commit->gerritChangeId();
		}
		catch (GitException $e) {
			$this->logger->warn("{$e->getMessage()}. Skipping commit.");
			throw new GitHookException("Couldn't get Change-Id for {$commit}", $e->getCode(), $e);
		}

		$change = new Change($changeId);

		try {
			if (!$change->exists()) {
				// This is not a warning, because some repositories do not require code review
				$this->logger->info('Skipping change b/c it does not exist in Gerrit');
				return;
			}

			$change->abandon("Abandoning from git hook for commit {$commit}.");
		}
		catch (GerritException $e) {
			$this->logger->error("Problem abandoning Gerrit review {$changeId}", $e);
			throw new GitHookException("Problem abandoning Gerrit review {$changeId}", $e->getCode(), $e);
		}
	}
}
