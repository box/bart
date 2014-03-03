<?php
namespace Bart\Gerrit;

use Bart\BaseTestCase;

class ChangeTest extends BaseTestCase
{
	private $fakeChangeId = 'Iabc123';
	private $fakeMergedHash = 'a7dd3f9';

	public function testCurrentPatchSet()
	{
		$stubApi = $this->stubApi();
		$this->registerDiesel('\Bart\Gerrit\Api', $stubApi);

		$change = new Change($this->fakeChangeId);

		$this->assertEquals(1, $change->currentPatchSetNumber());
	}

	public function testMarkMerged()
	{
		$stubApi = $this->stubApi();
		$stubApi->expects($this->exactly(2))
			->method('gsql')
			->will($this->returnCallback(function ($gsql, array $params) {
				$apiResult = new ApiResult(array('rowCount' => 1), array());
				if (strstr($gsql, 'UPDATE changes')) {
					$this->assertCount(1, $params, 'UPDATE params');
					$this->assertEquals($this->fakeChangeId, $params[0]);

					return $apiResult;
				} else if (strstr($gsql, 'INSERT INTO')) {
					$this->assertCount(4, $params, 'INSERT params');

					$this->assertEquals($this->fakeMergedHash, $params[0]);
					$this->assertEquals(9, $params[1]);
					$this->assertEquals(2583, $params[2]);
					$this->assertEquals(2, $params[3]);

					return $apiResult;
				}

				$this->fail('Unexpected GSQL sent to Api::gsql()  - ' . $gsql);
			}));

		$this->registerDiesel('\Bart\Gerrit\Api', $stubApi);

		$change = new Change($this->fakeChangeId);
		$change->markMerged($this->fakeMergedHash);
	}

	public function testNoMatchForChangeId()
	{
		$stubApi = $this->stubApi(0);
		$this->registerDiesel('\Bart\Gerrit\Api', $stubApi);

		$change = new Change($this->fakeChangeId);

		$this->assertFalse($change->exists(), 'Change exists?');
	}

	public function testValidChangeExists()
	{
		$stubApi = $this->stubApi();
		$this->registerDiesel('\Bart\Gerrit\Api', $stubApi);

		$change = new Change($this->fakeChangeId);

		$this->assertTrue($change->exists(), 'Change exists?');
	}

	/**
	 * @param int $rowCount 0 or 1 number of records to return
	 * @return \PHPUnit_Framework_MockObject_MockObject stub Gerrit\Api
	 */
	private function stubApi($rowCount = 1)
	{
		$gerritData = $rowCount == 1 ?
			array(
				'number' => '2583',
				'currentPatchSet' => array('number' => '1')
			) : array();

		$stubApi = $this->getMock('\Bart\Gerrit\Api', array(), array(), '', false);
		$stubApi->expects($this->once())
			->method('query')
			->with('--current-patch-set %s', array($this->fakeChangeId))
			->will($this->returnValue(new ApiResult(array('rowCount' => $rowCount), array($gerritData))));
		return $stubApi;
	}
}

