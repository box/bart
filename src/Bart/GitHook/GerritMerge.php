<?php
namespace Bart\GitHook;
use Bart\Gerrit\Change;
use Bart\Gerrit\GerritException;
use Bart\Git\Commit;
use Bart\GitException;

/**
 * Merge commit in Gerrit
 */
class GerritMerge extends GitHookAction
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
				$this->logger->debug('Skipping change b/c it does not exist in Gerrit');
				return;
			}

			$change->markMerged($commit->revision());
			$change->comment("Git hook marking {$changeId} as merged by {$commit}");
		}
		catch (GerritException $e) {
			$this->logger->error("Failed to mark Gerrit reivew {$changeId} as merged", $e);
			throw new GitHookException("Failed to mark Gerrit reivew {$changeId} as merged", $e->getCode(), $e);
		}
	}
}
