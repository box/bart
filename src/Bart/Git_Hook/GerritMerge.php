<?php
namespace Bart\Git_Hook;
use Bart\Gerrit\Change;
use Bart\Gerrit\GerritException;
use Bart\Git_Exception;

/**
 * Merge commit in Gerrit
 */
class GerritMerge extends GitHookAction
{
	/**
	 * Run the hook
	 * @param string $commitHash commit to verify
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
				$this->logger->debug('Skipping change b/c it does not exist in Gerrit');
				return;
			}

			$change->markMerged($commitHash);
		}
		catch (GerritException $e) {
			$this->logger->error('Problem while marking change merged in gerrit. Ignoring and moving on with hook.', $e);
		}
	}
}