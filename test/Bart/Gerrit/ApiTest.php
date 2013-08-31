<?php
namespace Bart\Gerrit;

use Bart\BaseTestCase;
use Bart\Configuration\GerritConfig;
use \Bart\Diesel;
use Bart\Shell\CommandException;
use \Bart\Witness;

class ApiTest extends BaseTestCase
{
	public $changeId = 'Iabcde123f';
	public $commitHash = 'abcde123fg';

	public function testSshFailureCaptured()
	{
		$g = $this->createGerritApiForQuery(255, '');

		$me = $this;
		$this->assertThrows('Bart\Gerrit\GerritException', 'Query to gerrit failed', function() use($g, $me) {
			$g->getApprovedChange($me->changeId, $me->commitHash);
		});
	}

	public function testBadJson()
	{
		$g = $this->createGerritApiForQuery(0, array('this json is not formatted'));

		$me = $this;
		$this->assertThrows('Bart\Gerrit\GerritException', 'returned bad json', function() use($g, $me) {
			$g->getApprovedChange($me->changeId, $me->commitHash);
		});
	}

	public function testEmptyResults()
	{
		$json = array('{"type":"stats","rowCount":0,"runTimeMilliseconds":16}');
		$g = $this->createGerritApiForQuery(0, $json);
		$changeData = $g->getApprovedChange($this->changeId, $this->commitHash);

		$this->assertNull($changeData, 'Empty record set should return null');
	}

	public function testMoreThanOneRecordReturned()
	{
		// Gerrit matched two records
		$json = array('{}', '{}', '{"type":"stats","rowCount":2,"runTimeMilliseconds":16}');
		$g = $this->createGerritApiForQuery(0, $json);

		$me = $this;
		$msg = 'More than one gerrit record matched';
		$this->assertThrows('Bart\Gerrit\GerritException', $msg, function() use($g, $me) {
			$g->getApprovedChange($me->changeId, $me->commitHash);
		});

	}

	public function testLegitResponse()
	{
		$json = array(
			'{"project":"scm","branch":"v5-dev","topic":"remove_collab",'
				. '"id":"Iabcde123f","number":"654321",'
				. '"subject":"BOX-123 The internet is slow",'
				. '"owner":{"name":"Atul Bhatia",'
				. '"email":"abhatia@box.com"},"url":"http:/gerrit:8080/654321",'
				. '"lastUpdated":1326935062,"sortKey":"001a7a6000005f95","open":false,'
				. '"status":"MERGED"}',
			'{"type":"stats","rowCount":1,"runTimeMilliseconds":16}',
		);

		$g = $this->createGerritApiForQuery(0, $json);
		$change = $g->getApprovedChange($this->changeId, $this->commitHash);

		$this->assertEquals('MERGED', $change['status'],
				'Gerrit change status not parsed correclty');
	}

	private function createGerritApiForQuery($status, $json)
	{
		$changeId = $this->changeId;
		$commitHash = $this->commitHash;

		$remote_gerrit_cmd = 'gerrit query --format=JSON ' . $changeId
				. " commit:$commitHash label:CodeReview=10";

		$will = ($status != 0) ?
			$this->throwException(new CommandException($status)) :
			$this->returnValue($json);

		$ssh = $this->getMock('\\Bart\\SshWrapper', array(), array(), '', false);
		$ssh->expects($this->once())
				->method('exec')
				->with($this->equalTo($remote_gerrit_cmd))
				->will($will);

		$phpu = $this;
		Diesel::registerInstantiator('Bart\SshWrapper',
			function($server, $port) use($ssh, $phpu) {
				$phpu->assertEquals('gerrit.example.com', $server, 'gerrit server');
				$phpu->assertEquals(29418, $port, 'gerrit ssh port');

				return $ssh;
			});

		/** @var GerritConfig $gerritConfig */
		$gerritConfigs = $this->getMock('Bart\Configuration\GerritConfig', array(), array(), '', false);
		$gerritConfigs->expects($this->once())
			->method('host')->will($this->returnValue('gerrit.example.com'));
		$gerritConfigs->expects($this->once())
			->method('sshPort')->will($this->returnValue(29418));
		$gerritConfigs->expects($this->once())
			->method('sshUser')->will($this->returnValue('gerrit'));
		$gerritConfigs->expects($this->once())
			->method('sshKeyFile')->will($this->returnValue('~/.ssh/keyFile'));

		Diesel::registerInstantiator('Bart\Configuration\GerritConfig', function() use ($gerritConfigs) {
			return $gerritConfigs;
		});

		return new Api();
	}
}
