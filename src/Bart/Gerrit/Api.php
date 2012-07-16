<?php
namespace Bart\Gerrit;

use Bart\Diesel;
use Bart\Witness;
use Bart\Ssh;

/**
 * Wrapper for the Gerrit API
 */
class Api
{
	private $ssh;

	/**
	 * @param array $conf Configurations for reaching Gerrit server
	 */
	public function __construct(array $conf, Witness $w = null)
	{
		$this->w = $w ?: new Witness\Silent();

		$this->ssh = Diesel::locateNew('Bart\Ssh', $conf['host']);
		$this->ssh->use_auto_user();
		$this->ssh->set_port($conf['port']);
	}

	/**
	 * Query gerrit for an approved change
	 *
	 * @param type $change_id Gerrit Change-Id
	 * @param type $commit_hash Latest commit hash pushed to gerrit
	 */
	public function get_approved_change($change_id, $commit_hash)
	{
		return $this->get_change($change_id, array(
			'commit' => $commit_hash,
			'label'=> 'CodeReview=10',
		));
	}

	/**
	 * @param array $filters e.g. array(
	 * 	'commit' => '$commit_hash',
	 *  'label' => 'CodeReview=10',
	 * )
	 * See http://scm.dev.box.net:8080/Documentation/user-search.html
	 */
	private function get_change($change_id, array $filters)
	{
		$filter_str = self::make_filter_string($filters);

		$remote_query = 'gerrit query --format=JSON ' . $change_id . $filter_str;
		$this->w->report("Calling gerrit $remote_query");
		$result = $this->ssh->execute($remote_query);

		if ($result['exit_status'] != 0)
		{
			throw new \Exception('Gerrit API Exception: ' . print_r($result['output'], true));
		}

		$gerrit_page = $result['output'];

		// No data returned, e.g.
		// ...[0] => {"type":"stats","rowCount":0,"runTimeMilliseconds":17}
		$record_count = count($gerrit_page);
		if ($record_count == 1)
		{
			return null;
		}

		if ($record_count > 2)
		{
			throw new \Exception('More than one gerrit record matched');
		}

		return json_decode($gerrit_page[0], true);
	}

	/**
	 * @return All the filters as a string
	 */
	private static function make_filter_string(array $filters)
	{
		$str = '';
		foreach($filters as $name => $filter)
		{
			$str .= " $name:$filter";
		}

		return $str;
	}
}
