<?php

/**
 * Wrapper for the Gerrit API
 */
class Gerrit_Api
{
	private $ssh;
	private $di;

	public function __construct(Diesel $di = null)
	{
		$this->di = $di ?: new Diesel();

		$parser = $this->di->create($this, 'Config_Parser');
		$conf = $parser->parse_conf_file(BART_DIR . 'etc/php/gerrit.conf');

		$this->ssh = $this->di->create($this, 'Ssh', array('server' => $conf['server']['host']));
		$this->ssh->set_port($conf['server']['port']);
	}

	public static function dieselify($me)
	{
		Diesel::register_global($me, 'Config_Parser', function() {
			return new Config_Parser();
		});

		Diesel::register_global($me, 'Ssh', function($params) {
			$ssh = new Ssh($params['server']);
			$ssh->use_auto_user();

			return $ssh;
		});
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
		$result = $this->ssh->execute($remote_query);

		if ($result['exit_status'] != 0)
		{
			throw new Exception('Gerrit API Exception: ' . print_r($result['output'], true));
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
			throw new Exception('More than one gerrit record matched');
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
