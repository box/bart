<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Git;

class Base_Test extends \Bart\BaseTestCase
{
	public function test_constructor()
	{
		$conf = array();

		// mock git and method get_change_id to return $repo
		$mock_git = $this->getMock('\\Bart\\Git', array(), array(), '', false);
		$mock_git->expects($this->once())
				->method('get_change_id')
				->will($this->returnValue('grinder'));

		$phpu = $this;
		$di = new Diesel();
		$di->register_local('Bart\\Git_Hook\\Test_Git_Hook', 'Git',
			function($params) use($mock_git, $phpu) {
				$phpu->assertEquals('.git', $params['git_dir'],
						'Expected constructor to get git dir');

				return $mock_git;
		});

		$hook = new Test_Git_Hook($conf, '.git', 'grinder', $di);
		$hook->verify($this);
	}

	/**
	 * @return array(
	 * 	'di' => Diesel to use in tests for Git_Hook_Base implementors,
	 *  'git' => mock_git that was registered for $class_name
	 * );
	 */
	public static function get_diesel($phpu, $class_name)
	{
		// mock git and method get change id to return $repo
		$mock_git = $phpu->getMock('\\Bart\\Git', array(), array(), '', false);

		$di = new Diesel();
		$di->register_local($class_name, 'Git',
			function($params) use($mock_git) {
				return $mock_git;
		});

		return array('di' => $di, 'git' => $mock_git);
	}
}

/*
 * Silly class to help us test that the base class will do its stuff
 */
class Test_Git_Hook extends Base
{
	public function __construct(array $hook_conf, $git_dir, $repo, Diesel $di)
	{
		parent::__construct($hook_conf, $git_dir, $repo, new \Bart\Witness(), $di);
	}

	public function verify($phpu)
	{
		$phpu->assertNotNull($this->git,
				'Expected git to be defined by Base constructor');

		// Somewhat contrived -- make sure the mock git is used
		// ...and that $this->repo was set
		$phpu->assertEquals($this->repo, $this->git->get_change_id(''),
				'Expected mock git to be called');
	}
}
