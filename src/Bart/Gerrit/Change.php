<?php
namespace Bart\Gerrit;
use Bart\Diesel;
use Bart\Log4PHP;
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
	private $_remoteData = null;
	/** @var \Logger */
	private $logger;

	/**
	 * @param string $changeId Gerrit Change-Id key
	 */
	public function __construct($changeId)
	{
		$this->api = Diesel::create('\Bart\Gerrit\Api');
		$this->changeId = $changeId;

		$this->logger = Log4PHP::getLogger(__CLASS__);
	}

	public function __toString()
	{
		return "{$this->changeId}";
	}

	/**
	 * @return bool If Gerrit knows about this change review
	 */
	public function exists()
	{
		// Thanks, PHP loose type checking
		return $this->remoteData() != false;
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
		// Add patch set record first so FK constraint is met on :changes table
		$this->addMergedPatchsetRecord($mergedHash);

		$markReviewMerged = "UPDATE changes SET open = 'N', status = 'M', mergeable = 'Y', current_patch_set_id = %s WHERE change_key = %s LIMIT 1;";

		$result = $this->api->gsql($markReviewMerged, array($this->currentPatchSetNumber() + 1, $this->changeId));

		$rowCount = $result->rowCount();
		if ($rowCount != 1) {
			$this->logger->warn("Failure marking {$this} merged. Expected row count = 1, got {$rowCount}");
		}
		else {
			$this->logger->info("Marked review {$this} as merged");
		}
	}

	/**
	 * Posts comment on the latest patch set
	 * @param string $comment
	 */
	public function comment($comment)
	{
		// We don't necessarily know the current commit hash, but we do know
		// ...the change_num and current patch set
		$uniqueId = "{$this->pk()},{$this->currentPatchSetNumber()}";

		$this->api->review($uniqueId, null, $comment);

		$this->logger->info("Commented on {$this}");
	}

	/**
	 * Abandon the change
	 * @param string $comment
	 */
	public function abandon($comment)
	{
		// We don't necessarily know the current commit hash, but we do know
		// ...the change_num and current patch set
		$uniqueId = "{$this->pk()},{$this->currentPatchSetNumber()}";

		$this->api->review($uniqueId, null, $comment, '--abandon');

		$this->logger->info("Abandoned {$this}");
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

	/**
	 * @return array Details of change from Gerrit
	 */
	private function remoteData()
	{
		if ($this->_remoteData === null) {
			$result = $this->api->query('--current-patch-set %s', array($this->changeId));

			if ($result->rowCount() == 0) {
				$this->logger->debug("No record exists in Gerrit for {$this->changeId}");
				$this->_remoteData = array();
			}
			else {
				// Use first result (should be the only one)
				$this->_remoteData = $result->rawRecords()[0];
			}
		}

		return $this->_remoteData;
	}
}