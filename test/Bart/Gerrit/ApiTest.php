<?php
namespace Bart\Gerrit;

use Bart\BaseTestCase;
use Bart\Configuration\GerritConfig;
use \Bart\Diesel;
use Bart\Shell\CommandException;

class ApiTest extends BaseTestCase
{
	public $changeId = 'Iabcde123f';
	public $commitHash = 'abcde123fg';

	/** @var GerritConfig $gerritConfig */
	private $gerritConfigs;

	public function setUp()
	{
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

		$this->gerritConfigs = $gerritConfigs;

		parent::setUp();
	}

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

	public function testLegitResponseFromGetApprovedChange()
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

	public function testReviewWithEscapedSingleQuotes()
	{
		$comment = "Reviewin' like a boss";
		$remoteGerritCmd = "gerrit review  --code-review -1 --message 'Reviewin'\\'' like a boss' $this->commitHash";

		$api = $this->configureApiForCmd($remoteGerritCmd, $this->returnValue(''));

		$api->review($this->commitHash, -1, $comment);
	}

	public function testReviewNoScore()
	{
		$api = $this->configureApiForCmd("gerrit review   --message 'Comment with no score' $this->commitHash", $this->returnValue(''));
		$api->review($this->commitHash, null, 'Comment with no score');
	}

	public function testReviewNoScoreWithOptions()
	{
		$api = $this->configureApiForCmd("gerrit review --restore  --message 'Comment with no score' $this->commitHash", $this->returnValue(''));
		$api->review($this->commitHash, null, 'Comment with no score', '--restore');
	}

	private function createGerritApiForQuery($status, $json)
	{
		$changeId = $this->changeId;
		$commitHash = $this->commitHash;

		$this->gerritConfigs->expects($this->once())
			->method('reviewScore')->will($this->returnValue(10));
		$this->gerritConfigs->expects($this->once())
			->method('verifiedScore')->will($this->returnValue(null));

		$remoteGerritCmd = 'gerrit query --format=JSON ' . $changeId
				. " commit:$commitHash label:CodeReview=10";

		$will = ($status != 0) ?
			$this->throwException(new CommandException($status)) :
			$this->returnValue($json);

		return $this->configureApiForCmd($remoteGerritCmd, $will);
	}

	/**
	 * @param String $remoteGerritCmd
	 * @param \PHPUnit_Framework_MockObject_Stub $will
	 * @return Api
	 */
	private function configureApiForCmd($remoteGerritCmd, $will)
	{
		$ssh = $this->getMock('\\Bart\\SshWrapper', array(), array(), '', false);
		$ssh->expects($this->once())
			->method('exec')
			->with($this->equalTo($remoteGerritCmd))
			->will($will);

		$gerritConfigs = $this->gerritConfigs;
		Diesel::registerInstantiator('Bart\Configuration\GerritConfig', function () use ($gerritConfigs) {
			return $gerritConfigs;
		});

		$phpu = $this;
		Diesel::registerInstantiator('Bart\SshWrapper',
			function ($server, $port) use ($ssh, $phpu) {
				$phpu->assertEquals('gerrit.example.com', $server, 'gerrit server');
				$phpu->assertEquals(29418, $port, 'gerrit ssh port');

				return $ssh;
			});

		return new Api();
	}
}
