<?php
namespace Bart\GitHook;
use Bart\Gerrit\Change;
use Bart\Gerrit\GerritException;
use Bart\GitException;

/**
 * Merge commit in Gerrit
 */
class GerritMerge extends GitHookAction
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
		catch (GitException $e) {
			$this->logger->warn("{$e->getMessage()}. Skipping commit.");
			return;
		}

		$change = new Change($changeId);

		try {
			if (!$change->exists()) {
				// This is not a warning, because some repositories do not require code review
				$this->logger->debug('Skipping change b/c it does not exist in Gerrit');
				return;
			}

			$change->markMerged($commitHash);
			$change->comment('Git hook marking this review as merged');
		}
		catch (GerritException $e) {
			$this->logger->error('Problem while marking change merged in gerrit. Ignoring and moving on with hook.', $e);
		}
	}
}
