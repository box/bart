<?php
/**
 * Enforces that a commit is approved in gerrit
 */
class Git_Hook_Gerrit_Approved extends Git_Hook_Base
{
	private $api;

	public function __construct(array $conf, $git_dir, $repo, Witness $w, Diesel $di)
	{
		$gerrit_conf = $conf['gerrit'];
		parent::__construct($gerrit_conf, $git_dir, $repo, $w, $di);

		$this->api = $di->create($this, 'Gerrit_Api',
				array('gerrit_conf' => $gerrit_conf, 'w' => $w));
	}

	public static function dieselify($me)
	{
		parent::dieselify($me);

		Diesel::register_global($me, 'Gerrit_Api', function($params) {
			return new Gerrit_Api($params['gerrit_conf'], $params['w']);
		});
	}

	public function verify($commit_hash)
	{
		// Let exception bubble up if no change id
		$change_id = $this->git->get_change_id($commit_hash);

		$data;
		try
		{
			$this->w->report('Getting data from gerrit: ' . $change_id);
			$data = $this->api->get_approved_change($change_id, $commit_hash);
		}
		catch(Exception $e)
		{
			throw new Exception('Error getting Gerrit review info', $e->getCode(), $e);
		}

		if ($data == null)
		{
			throw new Exception ('An approved review was not found in Gerrit for'
					. " commit $commit_hash with Change-Id $change_id");
		}

		$this->w->report('Gerrit approved.');
	}
}
