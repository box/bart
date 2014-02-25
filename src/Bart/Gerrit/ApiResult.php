<?php
namespace Bart\Gerrit;

/**
 * Result of a call to the Gerrit Api
 */
class ApiResult
{
	private $stats;
	private $records;

	/**
	 * @param array $stats Gerrit statistics on request
	 * @param array $records Array of the records returned by Gerrit
	 */
	public function __construct($stats, $records)
	{
		$this->stats = $stats;
		$this->records = $records;
	}

	/**
	 * @return int
	 */
	public function rowCount()
	{
		return $this->stats['rowCount'];
	}

	/**
	 * @return array Records parsed from the raw Gerrit JSON into associate PHP arrays
	 */
	public function rawRecords()
	{
		return $this->records;
	}
}