<?php
namespace Bart\Git_Hook;

use Bart\Configuration\GerritConfig;
use Bart\Diesel;
use Bart\Witness;

/**
 * Enforces that a commit is approved in gerrit
 */
class Gerrit_Approved extends Base
{
	/** @var \Bart\Gerrit\Api */
	private $api;

	public function __construct(array $conf, $git_dir, $repo, Witness $w)
	{
		$gerrit_conf = $conf['gerrit'];
		parent::__construct($gerrit_conf, $git_dir, $repo, $w);

		/** @var \Bart\Gerrit\Api api */
		$this->api = Diesel::create('Bart\Gerrit\Api');
	}

	public function verify($commit_hash)
	{
		// Let exception bubble up if no change id
		$change_id = $this->git->get_change_id($commit_hash);

		$data = null;
		try
		{
			$this->w->report('Getting data from gerrit: ' . $change_id);
			$data = $this->api->getApprovedChange($change_id, $commit_hash);
		}
		catch(\Exception $e)
		{
			throw new \Exception('Error getting Gerrit review info', $e->getCode(), $e);
		}

		if ($data == null)
		{
			throw new \Exception ('An approved review was not found in Gerrit for'
					. " commit $commit_hash with Change-Id $change_id");
		}

		$this->w->report('Gerrit approved.');
	}
}
