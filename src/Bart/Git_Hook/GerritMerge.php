<?php
namespace Bart\Git_Hook;
use Bart\Gerrit\Change;
use Bart\Gerrit\GerritException;

/**
 * Merge commit in Gerrit
 * Configuration:
[post-receive]
names = gerrit-merge

[gerrit-merge]
; Configurations for Gerrit are managed by \Bart\Configuration\GerritConfig class
; ...and do not appear here
class = GerritMerge
enabled = yes
 */
class GerritMerge extends GitHookAction
{
	/**
	 * Run the hook
	 * @param $commitHash string of commit to verify
	 * @throws GitHookException if requirement fails
	 */
	public function run($commitHash)
	{
		$changeId = $this->git->get_change_id($commitHash);

		$change = new Change($changeId);

		try
		{
			$change->markMerged($commitHash);
		}
		catch (GerritException $e)
		{
			$this->logger->error('Problem while marking change merged in gerrit. Ignoring and moving on with hook.', $e);
		}
	}
}