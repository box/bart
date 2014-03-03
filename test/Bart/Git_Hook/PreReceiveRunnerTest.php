<?php
namespace Bart\Git_Hook;

use Bart\Diesel;

class PreReceiveRunnerTest extends TestBase
{
	public function testConfKeyMissing()
	{
		$repo = 'Isengard';
		$hookConf = array(
			'pre-receive' => array('names' => 'jenkins'),
		);

		$preReceive = $this->configureFor($hookConf, $repo);

		$msg = 'No configuration section for hook jenkins';
		$closure = function() use ($preReceive) {
			$preReceive->runAllHooks('doesnt matter');
		};

		$this->assertThrows('\Exception', $msg, $closure);
	}

	public function test_no_class_exists()
	{
		$repo = 'Isengard';
		$monty = 'sir_not_appearing_in_this_film';
		$hookConf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => $monty,
				'enabled' => true,
			),
		);

		$preReceive = $this->configureFor($hookConf, $repo);

		$msg = "Hook action class (Bart\\Git_Hook\\$monty) does not exist";
		$closure = function() use ($preReceive) {
			$preReceive->runAllHooks('doesnt matter');
		};
		$this->assertThrows('\Bart\Git_Hook\GitHookException', $msg, $closure);
	}

	public function test_disabled_class()
	{
		$repo = 'Isengard';
		$hookConf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => 'Gerrit_Approved',
				'enabled' => false,
			),
		);

		$preReceive = $this->configureFor($hookConf, $repo);

		// Not necessarily accurate, but it should be true that if pre-receive
		// ...attempted to instantiate the Git_Hook it would crash when it
		// ...it couldn't find the dependency for class Git_Hook_Gerrit_Approved
		$preReceive->runAllHooks('doesnt matter');
	}

	public function test_verify_fails()
	{
		$repo = 'Isengard';
		$hookConf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => 'ForTesting',
				'verbose' => false,
				'enabled' => true,
				'super' => 'duper',
			),
		);

		$preReceive = $this->configureFor($hookConf, $repo);

		$phpu = $this;
		Diesel::registerInstantiator('Bart\Git_Hook\ForTesting', function() use ($phpu){
			return $phpu;
		});

		$preReceive->runAllHooks('doesnt matter');
	}

	private function configureFor($hookConf, $repo)
	{
		$gitDir = '.git';

		$mockConf = $this->getMock('\\Bart\\Config_Parser', array(), array(), '', false);
		$mockConf->expects($this->once())
				->method('parse_conf_file')
				->with($this->equalTo(BART_DIR . 'etc/php/hooks.conf'))
				->will($this->returnValue($hookConf));

		$phpu = $this;
		$createConf = function($repoParam) use ($phpu, $mockConf, $repo) {
			$phpu->assertEquals(array($repo), $repoParam,
					'Repo param to Config_Parser constructor');

			return $mockConf;
		};

		Diesel::registerInstantiator('Bart\Config_Parser', $createConf);

		return new PreReceiveRunner($gitDir, $repo);
	}
}

class ForTesting extends GitHookAction
{
	protected $conf;
	protected $dir;
	protected $repo;

	public function __construct(array $conf, $dir, $repo)
	{
		$this->conf = $conf;
		$this->dir = $dir;
		$this->repo = $repo;
	}

	public function run($commit_hash)
	{
		// Make sure everything got passed through as expected
		$phpu = Diesel::create('Bart\Git_Hook\ForTesting');
		$phpu->assertEquals('Isengard', $this->repo, 'Wrong repo passed');
		$phpu->assertEquals('.git', $this->dir, 'Wrong git dir passed');
		$phpu->assertEquals('duper', $this->conf['jenkins']['super'], 'Wrong conf passed');
	}
}
