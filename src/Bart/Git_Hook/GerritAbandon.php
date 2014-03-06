<?php
namespace Bart\Git_Hook;
use Bart\Gerrit\Change;
use Bart\Gerrit\GerritException;
use Bart\Git_Exception;

/**
 * Abandon Gerrit review
 */
class GerritAbandon extends GitHookAction
{
	/**
	 * Run the hook
	 * @param string $commitHash Commit with Gerrit Change-Id
	 * @throws GitHookException if requirement fails
	 */
	public function run($commitHash)
	{
		try {
			$changeId = $this->git->get_change_id($commitHash);
		}
		catch (Git_Exception $e) {
			$this->logger->warn("{$e->getMessage()}. Skipping commit.");
			return;
		}

		$change = new Change($changeId);

		try {
			if (!$change->exists()) {
				// This is not a warning, because some repositories do not require code review
				$this->logger->info('Skipping change b/c it does not exist in Gerrit');
				return;
			}

			$change->abandon("Abandoning from git hook for commit {$commitHash}.");
		}
		catch (GerritException $e) {
			$this->logger->error('Problem abandoning change. Ignoring and moving on with hook.', $e);
		}
	}
}