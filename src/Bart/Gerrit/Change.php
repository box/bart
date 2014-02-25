<?php
namespace Bart\Gerrit;
use Bart\Diesel;
use Bart\Shell\Command;

/**
 * A Gerrit change review
 */
class Change
{
	/** @var string The Gerrit Change-Id key */
	private $changeId;
	/** @var Api  */
	private $api;
	/** @var array Cached response from api */
	private $_remoteData;
	/** @var \Logger */
	private $logger;

	/**
	 * @param string $changeId Gerrit Change-Id key
	 */
	public function __construct($changeId)
	{
		$this->api = Diesel::create('\Bart\Gerrit\Api');
		$this->changeId = $changeId;

		$this->logger = \Logger::getLogger(__CLASS__);
	}

	public function __toString()
	{
		return "{$this->changeId}";
	}

	/**
	 * @return int The current patch set count
	 */
	public function currentPatchSetNumber()
	{
		$rawData = $this->remoteData();

		return $rawData['currentPatchSet']['number'];
	}

	/**
	 * @return int Gerrit `changes` table record's primary key
	 */
	public function pk()
	{
		$rawData = $this->remoteData();

		return $rawData['number'];
	}

	/**
	 * Use GSQL to mark review as merged.
	 * This is useful if reviews are being merged manually outside of Gerrit
	 * @param string $mergedHash The hash that was actually merged
	 */
	public function markMerged($mergedHash)
	{
		// First update changes db table
		// Query to undo: UPDATE changes SET open = 'Y', status = 'n', mergeable = 'N' WHERE change_id = 76641 LIMIT 1;
		$markReviewMerged = "UPDATE changes SET open = 'N', status = 'M', mergeable = 'Y' WHERE change_key = %s LIMIT 1;";

		$result = $this->api->gsql($markReviewMerged, array($this->changeId));

		$rowCount = $result->rowCount();
		if ($rowCount != 1) {
			$this->logger->warn("Failure marking {$this} merged. Expected row count = 1, got {$rowCount}");
		}
		else {
			$this->logger->info("Marked review {$this} as merged");
		}

		$this->addMergedPatchsetRecord($mergedHash);
	}

	/**
	 * Add a new record to patch_sets. Use this when marking a review merged
	 * @param string $mergedHash
	 */
	private function addMergedPatchsetRecord($mergedHash)
	{
		$insertMergedHash = 'INSERT INTO patch_sets '
			. '(revision, uploader_account_id, change_id, patch_set_id) '
		    . 'VALUES (%s, %s, %s, %s)';

		// TODO Get a real account_id. If not by query, then maybe use configurations
		// ...currently not too worried about it since it's not an FK
		$result = $this->api->gsql(
			$insertMergedHash,
			array($mergedHash, 9, $this->pk(), $this->currentPatchSetNumber() + 1
		));

		$rowCount = $result->rowCount();
		if ($rowCount != 1) {
			$this->logger->warn("Failure adding patch_set revision {$mergedHash} for {$this}. Expected row count = 1, got {$rowCount}");
		} else {
			$this->logger->info("Added new patch_set record {$mergedHash} to {$this}");
		}
	}

	private function remoteData()
	{
		if (!$this->_remoteData)
		{
			// TODO What if there is no match?
			$result = $this->api->query('--current-patch-set %s', array($this->changeId));

			// Use first result (should be the only one)
			$this->_remoteData = $result->rawRecords()[0];
		}

		return $this->_remoteData;
	}
}