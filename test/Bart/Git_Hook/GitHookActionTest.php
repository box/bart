<?php
namespace Bart\Git_Hook;

use Bart\Diesel;
use Bart\Git;

class GitHookActionTest extends \Bart\BaseTestCase
{
	public function testConstructor()
	{
		$conf = array();

		// mock git and method get_change_id to return $repo
		$mock_git = $this->getMock('\Bart\Git', array(), array(), '', false);
		$mock_git->expects($this->once())
				->method('get_change_id')
				->will($this->returnValue('grinder'));

		$phpu = $this;
		Diesel::registerInstantiator('Bart\Git',
			function($gitDir) use($mock_git, $phpu) {
				$phpu->assertEquals('.git', $gitDir,
						'Expected constructor to get git dir');

				return $mock_git;
		});

		$hook = new TestGitHookAction($conf, '.git', 'grinder');
		$hook->run($this);
	}
}

/**
 * Silly class to help us test that the base class will do its stuff
 */
class TestGitHookAction extends GitHookAction
{
	public function run($phpu)
	{
		$phpu->assertNotNull($this->git,
				'Expected git to be defined by Base constructor');

		// Somewhat contrived -- make sure the mock git is used
		// ...and that $this->repo was set
		$phpu->assertEquals($this->repo, $this->git->get_change_id(''),
				'Expected mock git to be called');
	}
}
