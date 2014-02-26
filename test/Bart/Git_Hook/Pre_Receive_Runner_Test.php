<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Witness;

class Pre_Receive_Runner_Test extends TestBase
{
	public function test_conf_key_missing()
	{
		$repo = 'Isengard';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
		);

		$pre_receive = $this->configure_for($hook_conf, $repo);

		$msg = 'No configuration section for hook jenkins';
		$closure = function() use ($pre_receive) {
			$pre_receive->verify_all('doesnt matter');
		};

		$this->assertThrows('\Exception', $msg, $closure);
	}

	public function test_no_class_exists()
	{
		$repo = 'Isengard';
		$monty = 'sir_not_appearing_in_this_film';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => $monty,
				'enabled' => true,
			),
		);

		$pre_receive = $this->configure_for($hook_conf, $repo);

		$msg = "Class for hook does not exist! (Bart\\Git_Hook\\$monty)";
		$closure = function() use ($pre_receive) {
			$pre_receive->verify_all('doesnt matter');
		};
		$this->assertThrows('\Bart\Git_Hook\GitHookException', $msg, $closure);
	}

	public function test_disabled_class()
	{
		$repo = 'Isengard';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => 'Gerrit_Approved',
				'enabled' => false,
			),
		);

		$pre_receive = $this->configure_for($hook_conf, $repo);

		// Not necessarily accurate, but it should be true that if pre-receive
		// ...attempted to instantiate the Git_Hook it would crash when it
		// ...it couldn't find the dependency for class Git_Hook_Gerrit_Approved
		$pre_receive->verify_all('doesnt matter');
	}

	public function test_verify_fails()
	{
		$repo = 'Isengard';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => 'For_Testing',
				'verbose' => false,
				'enabled' => true,
				'super' => 'duper',
			),
		);

		$pre_receive = $this->configure_for($hook_conf, $repo);

		$phpu = $this;
		Diesel::registerInstantiator('Bart\Git_Hook\For_Testing', function() use ($phpu){
			return $phpu;
		});

		$pre_receive->verify_all('doesnt matter');
	}

	private function configure_for($hook_conf, $repo)
	{
		$git_dir = '.git';
		$w = new Witness\Silent();

		$mock_conf = $this->getMock('\\Bart\\Config_Parser', array(), array(), '', false);
		$mock_conf->expects($this->once())
				->method('parse_conf_file')
				->with($this->equalTo(BART_DIR . 'etc/php/hooks.conf'))
				->will($this->returnValue($hook_conf));

		$phpu = $this;
		$create_conf = function($repoParam) use ($phpu, $mock_conf, $repo) {
			$phpu->assertEquals(array($repo), $repoParam,
					'Repo param to Config_Parser constructor');

			return $mock_conf;
		};

		\Bart\Diesel::registerInstantiator('Bart\Config_Parser', $create_conf);

		return new Pre_Receive_Runner($git_dir, $repo, $w);
	}
}

class For_Testing extends Base
{
	protected $conf;
	protected $dir;
	protected $repo;

	public function __construct(array $conf, $dir, $repo, Witness $w)
	{
		$this->conf = $conf;
		$this->dir = $dir;
		$this->repo = $repo;
	}

	public function run($commit_hash)
	{
		// Make sure everything got passed through as expected
		$phpu = Diesel::create('Bart\Git_Hook\For_Testing');
		$phpu->assertEquals('Isengard', $this->repo, 'Wrong repo passed');
		$phpu->assertEquals('.git', $this->dir, 'Wrong git dir passed');
		$phpu->assertEquals('duper', $this->conf['jenkins']['super'], 'Wrong conf passed');
	}
}
